<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Lead;
use App\Services\Ads\MicrosoftAdsOfflineConversionService;
use App\Services\Ads\MicrosoftQualifiedLeadService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Sends the frozen "Qualified Lead - 3+ Windows" conversion of one lead to Microsoft Ads.
 *
 * Independent from the phone-call upload: a confirmed call on the same click never
 * blocks this one, and this one never touches phone_clicks.
 */
class SendLeadQualifiedToMicrosoftAds implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 60;

    public function __construct(public readonly int $leadId)
    {
        $this->onQueue('default');
    }

    public function handle(MicrosoftAdsOfflineConversionService $microsoft): void
    {
        Cache::lock('ads:bing-qualified:'.$this->leadId, 90)->get(function () use ($microsoft): void {
            $lead = Lead::query()->find($this->leadId);
            if (! $lead || $lead->bing_qualified_sent_at !== null) {
                return;
            }

            if (! in_array($lead->bing_qualified_status, [Lead::BING_QUALIFIED_PENDING, Lead::BING_QUALIFIED_FAILED], true)) {
                return;
            }

            if (! $microsoft->isConfiguredForQualifiedLeads()) {
                $this->markFailed($lead, 'Microsoft Ads credentials or qualified conversion name are not configured.');

                return;
            }

            $lead->forceFill(['bing_qualified_attempts' => (int) $lead->bing_qualified_attempts + 1])->save();

            try {
                $microsoft->uploadQualifiedLead(
                    (string) $lead->bing_qualified_msclkid,
                    (string) $lead->bing_qualified_conversion_name,
                    $lead->bing_qualified_at,
                );
            } catch (Throwable $exception) {
                report($exception);
                $this->markFailed($lead, $exception->getMessage());

                return;
            }

            $lead->forceFill([
                'bing_qualified_status' => Lead::BING_QUALIFIED_SENT,
                'bing_qualified_sent_at' => now(),
                'bing_qualified_error' => null,
            ])->save();

            Log::info('Microsoft Ads qualified lead conversion sent', $this->logContext($lead));
        });
    }

    private function markFailed(Lead $lead, string $message): void
    {
        $lead->forceFill([
            'bing_qualified_status' => Lead::BING_QUALIFIED_FAILED,
            'bing_qualified_error' => Str::limit($message, 1000),
        ])->save();

        Log::warning('Microsoft Ads qualified lead conversion failed', $this->logContext($lead) + [
            'error' => Str::limit($message, 300),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function logContext(Lead $lead): array
    {
        return [
            'lead_id' => $lead->id,
            'conversion_name' => $lead->bing_qualified_conversion_name,
            'msclkid' => MicrosoftQualifiedLeadService::mask($lead->bing_qualified_msclkid),
            'conversion_time' => $lead->bing_qualified_at?->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
            'attempt' => (int) $lead->bing_qualified_attempts,
        ];
    }
}
