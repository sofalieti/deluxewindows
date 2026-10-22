<?php

declare(strict_types=1);

namespace App\Services\Ads;

use App\Jobs\SendLeadQualifiedToMicrosoftAds;
use App\Models\Lead;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Turns a clean lead whose visitor picked 3+ windows on the form into the Microsoft Ads
 * "Qualified Lead - 3+ Windows" conversion, once per project, and queues the upload.
 *
 * Mirrors the browser Google "Qualified Lead" rule without touching it.
 */
final class MicrosoftQualifiedLeadService
{
    /**
     * Visitor answers that mean 3 or more openings; the legacy buckets are kept for old forms.
     */
    public const ELIGIBLE_VISITOR_COUNTS = ['3-5', '6-10', '11plus'];

    private const ELIGIBLE_LEGACY_COUNTS = ['4-10', '11+'];

    public function __construct(
        private readonly MicrosoftAdsOfflineConversionService $microsoft,
    ) {}

    /**
     * Safe to call repeatedly: the first qualification is final.
     */
    public function evaluate(Lead $lead): void
    {
        if (! Schema::hasColumn('leads', 'bing_qualified_at')) {
            return;
        }

        $lead->refresh();

        if ($lead->bing_qualified_at !== null || $this->ineligibleReason($lead) !== null) {
            return;
        }

        // The visitor qualified the lead by submitting the form, so that is the conversion time.
        $qualifiedAt = ($lead->created_at ?? now())->copy()->startOfSecond();
        $msclkid = $lead->resolvedMsclkid();
        [$status, $reason] = $this->decide($lead, $msclkid, $qualifiedAt);

        $claimed = Lead::query()
            ->whereKey($lead->id)
            ->whereNull('bing_qualified_at')
            ->update([
                'bing_qualified_at' => $qualifiedAt,
                'bing_qualified_status' => $status,
                'bing_qualified_conversion_name' => $this->microsoft->qualifiedConversionName(),
                'bing_qualified_msclkid' => $msclkid,
                'bing_qualified_error' => $reason,
            ]);

        if ($claimed === 0) {
            return;
        }

        Log::info('Microsoft Ads qualified lead decided', [
            'lead_id' => $lead->id,
            'status' => $status,
            'reason' => $reason,
            'msclkid' => self::mask($msclkid),
            'qualified_at' => $qualifiedAt->copy()->utc()->toIso8601ZuluString(),
        ]);

        if ($status === Lead::BING_QUALIFIED_PENDING) {
            SendLeadQualifiedToMicrosoftAds::dispatch($lead->id)->afterCommit();
        }
    }

    /**
     * Why this lead is not a 3+ windows lead.
     */
    public function ineligibleReason(Lead $lead): ?string
    {
        if ($lead->status === Lead::STATUS_SPAM) {
            return 'Lead is spam.';
        }

        if (! in_array($lead->metaValue('window_count'), self::ELIGIBLE_VISITOR_COUNTS, true)
            && ! in_array($lead->metaValue('opening_count'), self::ELIGIBLE_LEGACY_COUNTS, true)) {
            return 'Visitor did not pick 3+ windows on the form.';
        }

        return null;
    }

    public static function mask(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return substr($value, 0, 4).'...'.substr($value, -2).' ('.strlen($value).' chars)';
    }

    /**
     * @return array{0: string, 1: ?string}
     */
    private function decide(Lead $lead, ?string $msclkid, CarbonInterface $qualifiedAt): array
    {
        if ($msclkid === null) {
            return [Lead::BING_QUALIFIED_SKIPPED, 'No MSCLKID on this lead or its phone click; not a Bing ad click. Google is handled separately.'];
        }

        $duplicate = $this->projectAlreadyQualified($lead, $msclkid);
        if ($duplicate !== null) {
            return [Lead::BING_QUALIFIED_SKIPPED, 'Project already qualified for Microsoft as lead #'.$duplicate.'.'];
        }

        $windowDays = max(1, (int) config('services.microsoft_ads.qualified_window_days', 90));
        if ($qualifiedAt->lt(now()->subDays($windowDays))) {
            return [Lead::BING_QUALIFIED_SKIPPED, 'Lead is older than '.$windowDays.' days; outside the conversion window.'];
        }

        return [Lead::BING_QUALIFIED_PENDING, null];
    }

    /**
     * A form and a call about the same project must count once.
     */
    private function projectAlreadyQualified(Lead $lead, string $msclkid): ?int
    {
        $email = (string) $lead->normalized_email;
        if (str_ends_with($email, '@click.local')) {
            $email = '';
        }

        $id = Lead::query()
            ->whereKeyNot($lead->id)
            ->whereIn('bing_qualified_status', [
                Lead::BING_QUALIFIED_PENDING,
                Lead::BING_QUALIFIED_SENT,
                Lead::BING_QUALIFIED_FAILED,
            ])
            ->where(function ($query) use ($lead, $email, $msclkid): void {
                $query->where('bing_qualified_msclkid', $msclkid);
                if ($lead->contact_id !== null) {
                    $query->orWhere('contact_id', $lead->contact_id);
                }
                if (trim((string) $lead->normalized_phone, '0+') !== '') {
                    $query->orWhere('normalized_phone', $lead->normalized_phone);
                }
                if ($email !== '') {
                    $query->orWhere('normalized_email', $email);
                }
            })
            ->orderBy('id')
            ->value('id');

        return $id !== null ? (int) $id : null;
    }
}
