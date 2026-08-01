<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PhoneClick;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PhoneClickGoogleBridge
{
    /**
     * @return array{ok: bool, message: string, already_sent: bool}
     */
    public function sendOnce(PhoneClick $click, int $userId): array
    {
        $lock = Cache::lock('phone-click-google-sheet:'.$click->id, 45);

        try {
            return $lock->block(5, function () use ($click, $userId): array {
                $click->refresh();

                if ($click->wasSentToGoogleSheet()) {
                    return [
                        'ok' => true,
                        'message' => 'This phone click was already sent.',
                        'already_sent' => true,
                    ];
                }

                $result = $this->send($click);
                if (! $result['ok']) {
                    return [
                        ...$result,
                        'already_sent' => false,
                    ];
                }

                $click->forceFill([
                    'google_sheet_sent_at' => now(),
                    'google_sheet_sent_by' => $userId,
                ])->save();

                return [
                    ...$result,
                    'already_sent' => false,
                ];
            });
        } catch (LockTimeoutException) {
            return [
                'ok' => false,
                'message' => 'This phone click is already being sent. Please wait a moment.',
                'already_sent' => false,
            ];
        }
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function send(PhoneClick $click): array
    {
        if ($click->ringCentralClientPhone() === null) {
            return [
                'ok' => false,
                'message' => 'Match a RingCentral call first — Google Sheet needs the client phone number.',
            ];
        }

        $urls = array_values(array_filter(
            (array) config('services.lead_bridge.urls', []),
            fn (mixed $url): bool => is_string($url) && trim($url) !== ''
        ));

        if ($urls === []) {
            return [
                'ok' => false,
                'message' => 'Google Sheet bridge is not configured.',
            ];
        }

        $meta = is_array($click->meta) ? $click->meta : [];
        $bridgeStatuses = is_array($meta['google_sheet_bridges'] ?? null)
            ? $meta['google_sheet_bridges']
            : [];
        $errors = [];

        foreach ($urls as $url) {
            $url = trim($url);
            $bridgeKey = substr(hash('sha256', $url), 0, 20);

            if (! empty($bridgeStatuses[$bridgeKey]['sent_at'])) {
                continue;
            }

            try {
                $response = Http::asForm()
                    ->acceptJson()
                    ->connectTimeout(5)
                    ->timeout(20)
                    ->retry(2, 250, throw: false)
                    ->withOptions(['allow_redirects' => true])
                    ->post($url, $this->payload($click));

                if (! $response->successful()) {
                    $errors[] = 'Google bridge returned HTTP '.$response->status().'.';

                    Log::warning('Phone click Google Sheet delivery failed', [
                        'phone_click_id' => $click->id,
                        'bridge_host' => parse_url($url, PHP_URL_HOST),
                        'status' => $response->status(),
                    ]);

                    continue;
                }

                $bridgeStatuses[$bridgeKey] = [
                    'sent_at' => now()->toIso8601String(),
                    'status' => $response->status(),
                ];
                $meta['google_sheet_bridges'] = $bridgeStatuses;

                // Save each successful destination separately. A later retry skips
                // destinations that already accepted this phone click.
                $click->forceFill(['meta' => $meta])->save();
            } catch (\Throwable $exception) {
                $errors[] = 'Could not connect to a Google bridge.';

                Log::warning('Phone click Google Sheet delivery exception', [
                    'phone_click_id' => $click->id,
                    'bridge_host' => parse_url($url, PHP_URL_HOST),
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if ($errors !== []) {
            return [
                'ok' => false,
                'message' => implode(' ', array_unique($errors)),
            ];
        }

        return [
            'ok' => true,
            'message' => 'Phone click sent to Google Sheet.',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function payload(PhoneClick $click): array
    {
        $pageUrl = trim((string) $click->page_url);
        $clientPhone = (string) $click->ringCentralClientPhone();

        return [
            'Form ID' => 'Phone Click',
            'form_id' => 'Phone Click',
            'Page' => $pageUrl,
            'page_url' => $pageUrl,
            'URL' => $pageUrl,
            'Name' => '',
            'Email' => '',
            'Phone' => $clientPhone,
            'Subject' => (string) ($click->source_label ?? 'Phone click'),
            'Message' => 'Phone click #'.$click->id,
            'landing_page' => (string) ($click->landing_page ?? ''),
            'referrer' => (string) ($click->referrer ?? ''),
            'geo_location' => (string) ($click->geo_location ?? ''),
            'utm_source' => (string) ($click->utm_source ?? ''),
            'utm_medium' => (string) ($click->utm_medium ?? ''),
            'utm_campaign' => (string) ($click->utm_campaign ?? ''),
            'utm_content' => (string) ($click->utm_content ?? ''),
            'utm_term' => (string) ($click->utm_term ?? ''),
            'matchtype' => (string) ($click->matchtype ?? ''),
            'device' => (string) ($click->device ?? ''),
            'creative' => (string) ($click->creative ?? ''),
            'gclid' => (string) ($click->gclid ?? ''),
            'fbclid' => (string) ($click->fbclid ?? ''),
            'msclkid' => (string) ($click->msclkid ?? ''),
            'phone_click_id' => (string) $click->id,
            'idempotency_key' => 'phone-click-'.$click->id,
        ];
    }
}
