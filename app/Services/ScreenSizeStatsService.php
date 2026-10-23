<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lead;
use App\Models\PhoneClick;
use App\Models\SiteVisit;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ScreenSizeStatsService
{
    public const STEP = 50;

    /**
     * @return list<array{bucket: int, label: string, visits: int, leads: int, lead_rate: float|null, calls: int, call_rate: float|null}>
     */
    public function rows(CarbonInterface $start, CarbonInterface $end): array
    {
        $visits = $this->buckets(
            SiteVisit::query()
                ->whereBetween('created_at', [$start, $end])
                ->whereNotNull('screen_width')
                ->pluck('screen_width')
        );

        $leads = $this->buckets(
            Lead::query()
                ->where('status', '!=', Lead::STATUS_SPAM)
                ->whereBetween('created_at', [$start, $end])
                ->whereNotNull('screen_width')
                ->pluck('screen_width')
        );

        $calls = $this->buckets(
            PhoneClick::query()
                ->notSpam()
                ->where('ringcentral_status', PhoneClick::RINGCENTRAL_FOUND)
                ->whereBetween('created_at', [$start, $end])
                ->whereNotNull('screen_width')
                ->get(['screen_width', 'ringcentral_result', 'ringcentral_duration'])
                ->filter(fn (PhoneClick $click) => $click->isConnectedCall())
                ->pluck('screen_width')
        );

        $keys = $visits->keys()->merge($leads->keys())->merge($calls->keys())->unique()->sort()->values();

        return $keys->map(function (int $bucket) use ($visits, $leads, $calls): array {
            $visitCount = (int) ($visits[$bucket] ?? 0);
            $leadCount = (int) ($leads[$bucket] ?? 0);
            $callCount = (int) ($calls[$bucket] ?? 0);

            return [
                'bucket' => $bucket,
                'label' => $bucket.'-'.($bucket + self::STEP - 1),
                'visits' => $visitCount,
                'leads' => $leadCount,
                'lead_rate' => $visitCount > 0 ? round(($leadCount / $visitCount) * 100, 1) : null,
                'calls' => $callCount,
                'call_rate' => $visitCount > 0 ? round(($callCount / $visitCount) * 100, 1) : null,
            ];
        })->all();
    }

    /**
     * @param  Collection<int, mixed>  $widths
     * @return Collection<int, int>
     */
    private function buckets(Collection $widths): Collection
    {
        return $widths
            ->map(fn ($width) => (int) (floor(((int) $width) / self::STEP) * self::STEP))
            ->countBy()
            ->mapWithKeys(fn (int $count, int|string $bucket) => [(int) $bucket => $count]);
    }
}
