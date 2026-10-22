<?php

declare(strict_types=1);

namespace App\Services\Ads;

use App\Jobs\SendLeadQualifiedToMicrosoftAds;
use App\Models\Lead;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Decides once per project whether a staff-confirmed lead becomes the Microsoft Ads
 * "Qualified Lead - 3+ Windows" conversion, freezes what will be sent and queues it.
 *
 * Runs next to the Google appointment export but never changes it: Google keeps its
 * own rules, Bing gets the stricter staff-confirmed ones.
 */
final class MicrosoftQualifiedLeadService
{
    /**
     * Appointment is the qualification step; later stages imply it already happened.
     */
    public const QUALIFIED_STATUSES = [
        Lead::STATUS_APPOINTMENT,
        Lead::STATUS_QUOTED,
        Lead::STATUS_WON,
        Lead::STATUS_SOLD,
    ];

    private const ELIGIBLE_WINDOW_COUNTS = ['3-4', '5-9', '10plus'];

    private const ELIGIBLE_PROJECT_TYPES = ['replacement', 'new_construction'];

    /**
     * @var array<string, true>|null
     */
    private ?array $serviceZips = null;

    public function __construct(
        private readonly MicrosoftAdsOfflineConversionService $microsoft,
    ) {}

    /**
     * Safe to call on every save: the first qualification is final.
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

        $qualifiedAt = now()->startOfSecond();
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
     * Why the staff qualification does not (yet) make this a 3+ windows project.
     */
    public function ineligibleReason(Lead $lead): ?string
    {
        if (! in_array((string) $lead->status, self::QUALIFIED_STATUSES, true)) {
            return 'Status is not Appointment or later.';
        }

        if (! in_array((string) $lead->qualification_window_count, self::ELIGIBLE_WINDOW_COUNTS, true)) {
            return 'Confirmed window count is not 3+.';
        }

        if (! in_array((string) $lead->qualification_project_type, self::ELIGIBLE_PROJECT_TYPES, true)) {
            return 'Project type is not window replacement or new construction.';
        }

        if ($lead->qualification_property_type !== 'house') {
            return 'Property is not a private house.';
        }

        if (! $this->isServiceZip($lead->qualification_zip)) {
            return 'ZIP is outside the service area.';
        }

        if ($lead->qualification_decision_maker !== true) {
            return 'Contact is not confirmed as owner / decision maker.';
        }

        return null;
    }

    public function isServiceZip(?string $zip): bool
    {
        $digits = substr(preg_replace('/\D/', '', (string) $zip) ?? '', 0, 5);

        return strlen($digits) === 5 && isset($this->serviceZips()[$digits]);
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
        if ($lead->created_at !== null && $lead->created_at->lt($qualifiedAt->copy()->subDays($windowDays))) {
            return [Lead::BING_QUALIFIED_SKIPPED, 'Qualified more than '.$windowDays.' days after the ad click; outside the conversion window.'];
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

    /**
     * @return array<string, true>
     */
    private function serviceZips(): array
    {
        if ($this->serviceZips !== null) {
            return $this->serviceZips;
        }

        $this->serviceZips = [];
        $path = database_path('data/city-zips.json');
        $data = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;

        foreach ((array) $data as $zips) {
            foreach ((array) $zips as $zip) {
                $this->serviceZips[(string) $zip] = true;
            }
        }

        return $this->serviceZips;
    }
}
