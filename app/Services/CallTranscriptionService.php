<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RingCentralCall;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class CallTranscriptionService
{
    public function __construct(
        private readonly RingCentralCallLogService $callLog,
        private readonly CallTranscriptionEligibility $eligibility,
    ) {}

    public function process(RingCentralCall $call, bool $force = false): void
    {
        if (! $this->eligibility->isEligible($call, force: $force)) {
            throw new RuntimeException('Call is not eligible for transcription.');
        }

        $recordingId = $call->resolvedRecordingId();
        if ($recordingId === null) {
            throw new RuntimeException('Call has no recording id.');
        }

        $apiKey = trim((string) config('services.openai.api_key'));
        if ($apiKey === '') {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $call->forceFill([
            'transcript_status' => RingCentralCall::TRANSCRIPT_PROCESSING,
            'transcript_error' => null,
        ])->save();

        $tempPath = null;

        try {
            $audio = $this->callLog->fetchRecordingContent($recordingId);
            if (! $audio->successful()) {
                throw new RuntimeException('RingCentral recording download failed HTTP '.$audio->status().'.');
            }

            $tempPath = tempnam(sys_get_temp_dir(), 'rc-audio-');
            if ($tempPath === false) {
                throw new RuntimeException('Could not create a temporary audio file.');
            }
            $audioPath = $tempPath.'.mp3';
            rename($tempPath, $audioPath);
            $tempPath = $audioPath;
            file_put_contents($tempPath, $audio->body());

            $transcript = $this->transcribeFile($tempPath, $apiKey);
            $summary = $this->summarizeTranscript($transcript, $apiKey);

            $minutes = max(0, (int) $call->duration) / 60;
            $call->forceFill([
                'transcript_status' => RingCentralCall::TRANSCRIPT_COMPLETED,
                'transcript' => $transcript,
                'transcript_summary' => $summary,
                'transcript_processed_at' => now(),
                'transcript_error' => null,
                'transcript_meta' => [
                    'transcription_model' => (string) config('services.openai.transcription_model'),
                    'summary_model' => (string) config('services.openai.summary_model'),
                    'duration_seconds' => (int) $call->duration,
                    'estimated_minutes' => round($minutes, 2),
                    'estimated_cost_usd' => round(0.003 * $minutes, 4),
                ],
            ])->save();
        } catch (Throwable $exception) {
            Log::warning('Call transcription failed', [
                'ringcentral_call_id' => $call->ringcentral_call_id,
                'error' => $exception->getMessage(),
            ]);

            $call->forceFill([
                'transcript_status' => RingCentralCall::TRANSCRIPT_FAILED,
                'transcript_processed_at' => now(),
                'transcript_error' => $exception->getMessage(),
            ])->save();

            throw $exception;
        } finally {
            if (is_string($tempPath) && is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    private function transcribeFile(string $path, string $apiKey): string
    {
        $base = (string) config('services.openai.base_url');
        $model = (string) config('services.openai.transcription_model', 'gpt-4o-mini-transcribe');

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(180)
            ->attach('file', file_get_contents($path) ?: '', basename($path))
            ->post($base.'/audio/transcriptions', [
                'model' => $model,
                'response_format' => 'json',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI transcription failed HTTP '.$response->status().'.');
        }

        $text = trim((string) ($response->json('text') ?? ''));
        if ($text === '') {
            throw new RuntimeException('OpenAI transcription returned an empty transcript.');
        }

        return $text;
    }

    /**
     * @return array<string, mixed>
     */
    private function summarizeTranscript(string $transcript, string $apiKey): array
    {
        $base = (string) config('services.openai.base_url');
        $model = (string) config('services.openai.summary_model', 'gpt-5.4-nano');

        $schema = [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'overview' => ['type' => 'string'],
                'agreements' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'next_steps' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'objections' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'appointment' => ['type' => ['string', 'null']],
                'quote_discussed' => ['type' => ['string', 'null']],
            ],
            'required' => [
                'overview',
                'agreements',
                'next_steps',
                'objections',
                'appointment',
                'quote_discussed',
            ],
        ];

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(90)
            ->post($base.'/chat/completions', [
                'model' => $model,
                'temperature' => 0.2,
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'call_summary',
                        'strict' => true,
                        'schema' => $schema,
                    ],
                ],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You summarize home improvement sales phone calls for a CRM. '
                            .'Write in clear English. Focus on what was agreed, next steps, objections, '
                            .'appointments, and quotes. If something was not discussed, use an empty list or null.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Call transcript:\n\n".$transcript,
                    ],
                ],
            ]);

        if (! $response->successful()) {
            // Fallback without strict json_schema for older/cheaper models.
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->connectTimeout(10)
                ->timeout(90)
                ->post($base.'/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.2,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Return JSON with keys: overview (string), agreements (string[]), '
                                .'next_steps (string[]), objections (string[]), appointment (string|null), '
                                .'quote_discussed (string|null). English only.',
                        ],
                        [
                            'role' => 'user',
                            'content' => "Call transcript:\n\n".$transcript,
                        ],
                    ],
                ]);
        }

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI summary failed HTTP '.$response->status().'.');
        }

        $content = trim((string) data_get($response->json(), 'choices.0.message.content', ''));
        $decoded = json_decode($content, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('OpenAI summary returned invalid JSON.');
        }

        return [
            'overview' => trim((string) ($decoded['overview'] ?? '')),
            'agreements' => array_values(array_filter(array_map('strval', (array) ($decoded['agreements'] ?? [])))),
            'next_steps' => array_values(array_filter(array_map('strval', (array) ($decoded['next_steps'] ?? [])))),
            'objections' => array_values(array_filter(array_map('strval', (array) ($decoded['objections'] ?? [])))),
            'appointment' => isset($decoded['appointment']) && $decoded['appointment'] !== null
                ? trim((string) $decoded['appointment'])
                : null,
            'quote_discussed' => isset($decoded['quote_discussed']) && $decoded['quote_discussed'] !== null
                ? trim((string) $decoded['quote_discussed'])
                : null,
        ];
    }
}
