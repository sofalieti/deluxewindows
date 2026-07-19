<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PromotionControl;
use App\Models\Webflow\GlobalSettingsWebflowItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class PromotionCalendarService
{
    public function __construct(
        private readonly PromotionControlService $controls,
    ) {
    }

    /**
     * Build a contiguous Jan 1–Dec 31 calendar for $year covering every
     * U.S. federal holiday plus major retail holidays, with seasonal
     * filler titles for the gaps.
     *
     * @return list<array{title: string, start: string, end: string, kind: string}>
     */
    public function defaultPeriodsForYear(int $year): array
    {
        $anchors = $this->holidayAnchors($year);
        usort($anchors, fn (array $a, array $b): int => $a['start']->timestamp <=> $b['start']->timestamp);

        $merged = $this->mergeOverlaps($anchors);
        $filled = $this->fillYearGaps($merged, $year);

        return array_map(static fn (array $p): array => [
            'title' => $p['title'],
            'start' => $p['start']->toDateString(),
            'end' => $p['end']->toDateString(),
            'kind' => $p['kind'],
        ], $filled);
    }

    /**
     * @param list<array{title: string, start: string, end: string, kind?: string}>|null $periods
     * @return list<array{title: string, start: string, end: string, kind: string}>
     */
    public function normalizePeriods(?array $periods, ?int $year = null): array
    {
        $year ??= (int) now('America/Los_Angeles')->year;
        if ($periods === null || $periods === []) {
            return $this->defaultPeriodsForYear($year);
        }

        $normalized = [];
        foreach ($periods as $period) {
            if (! is_array($period)) {
                continue;
            }
            $title = trim((string) ($period['title'] ?? ''));
            $start = trim((string) ($period['start'] ?? ''));
            $end = trim((string) ($period['end'] ?? ''));
            if ($title === '' || $start === '' || $end === '') {
                continue;
            }
            try {
                $startDate = Carbon::parse($start)->startOfDay();
                $endDate = Carbon::parse($end)->startOfDay();
            } catch (\Throwable) {
                continue;
            }
            if ($endDate->lt($startDate)) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }
            $normalized[] = [
                'title' => $title,
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
                'kind' => trim((string) ($period['kind'] ?? 'custom')) ?: 'custom',
            ];
        }

        usort($normalized, fn (array $a, array $b): int => strcmp($a['start'], $b['start']));

        return $normalized !== [] ? $normalized : $this->defaultPeriodsForYear($year);
    }

    /**
     * @param list<array{title: string, start: string, end: string, kind?: string}> $periods
     * @return array{title: string, start: string, end: string, kind: string}|null
     */
    public function activePeriod(array $periods, ?Carbon $today = null): ?array
    {
        $today ??= now('America/Los_Angeles')->startOfDay();
        $todayStr = $today->toDateString();

        foreach ($this->normalizePeriods($periods) as $period) {
            if ($todayStr >= $period['start'] && $todayStr <= $period['end']) {
                return $period;
            }
        }

        return null;
    }

    /**
     * Apply the matching calendar title (and end date) to Global Promotion Title.
     *
     * @return array{changed: bool, title: string|null, end: string|null}
     */
    public function applyCurrentPeriod(?Carbon $today = null): array
    {
        if (! Schema::hasTable('promotion_controls')) {
            return ['changed' => false, 'title' => null, 'end' => null];
        }

        $today ??= now('America/Los_Angeles')->startOfDay();
        $control = $this->controls->get();
        $periods = $this->ensurePeriodsForYear($control, (int) $today->year);
        $active = $this->activePeriod($periods, $today);

        if ($active === null) {
            return ['changed' => false, 'title' => null, 'end' => null];
        }

        $currentTitle = trim((string) ($control->global_promotion_name ?? ''));
        $currentEnd = optional($control->global_end_date)->format('Y-m-d');
        $changed = $currentTitle !== $active['title'] || $currentEnd !== $active['end'];

        if ($changed) {
            $control->global_promotion_name = $active['title'];
            $control->global_end_date = $active['end'];
            $control->save();
            $this->syncLegacyGlobalSettings(
                Carbon::parse($active['end'])->format('n/j/y'),
                $active['title']
            );
            $this->controls->forgetCache();
        }

        return [
            'changed' => $changed,
            'title' => $active['title'],
            'end' => $active['end'],
        ];
    }

    /**
     * Make sure calendar_periods covers $year; regenerate when empty or stale.
     *
     * @return list<array{title: string, start: string, end: string, kind: string}>
     */
    public function ensurePeriodsForYear(PromotionControl $control, int $year): array
    {
        $existing = is_array($control->calendar_periods) ? $control->calendar_periods : [];
        $normalized = $this->normalizePeriods($existing, $year);

        $coversYear = false;
        foreach ($normalized as $period) {
            if (str_starts_with($period['start'], (string) $year) || str_starts_with($period['end'], (string) $year)) {
                $coversYear = true;
                break;
            }
        }

        if ($existing === [] || ! $coversYear) {
            $normalized = $this->defaultPeriodsForYear($year);
            if (Schema::hasColumn('promotion_controls', 'calendar_periods')) {
                $control->calendar_periods = $normalized;
                $control->save();
                $this->controls->forgetCache();
            }
        }

        return $normalized;
    }

    /**
     * @return list<array{title: string, start: Carbon, end: Carbon, kind: string}>
     */
    private function holidayAnchors(int $year): array
    {
        $mlk = $this->nthWeekday($year, 1, Carbon::MONDAY, 3);
        $presidents = $this->nthWeekday($year, 2, Carbon::MONDAY, 3);
        $memorial = $this->lastWeekday($year, 5, Carbon::MONDAY);
        $labor = $this->nthWeekday($year, 9, Carbon::MONDAY, 1);
        $columbus = $this->nthWeekday($year, 10, Carbon::MONDAY, 2);
        $thanksgiving = $this->nthWeekday($year, 11, Carbon::THURSDAY, 4);
        $easter = $this->easterSunday($year);
        $mothers = $this->nthWeekday($year, 5, Carbon::SUNDAY, 2);
        $fathers = $this->nthWeekday($year, 6, Carbon::SUNDAY, 3);

        $anchors = [
            $this->window("New Year's Sale", Carbon::create($year, 1, 1), 0, 6, 'holiday'),
            $this->window('Martin Luther King Jr. Day Sale', $mlk, 3, 3, 'holiday'),
            $this->window("Valentine's Day Sale", Carbon::create($year, 2, 14), 5, 2, 'holiday'),
            $this->window("Presidents' Day Sale", $presidents, 3, 3, 'holiday'),
            $this->window("St. Patrick's Day Sale", Carbon::create($year, 3, 17), 3, 3, 'holiday'),
            $this->window('Easter Sale', $easter, 5, 2, 'holiday'),
            $this->window("Mother's Day Sale", $mothers, 4, 2, 'holiday'),
            $this->window('Memorial Day Sale', $memorial, 4, 2, 'holiday'),
            $this->window('Juneteenth Sale', Carbon::create($year, 6, 19), 1, 0, 'holiday'),
            $this->window("Father's Day Sale", $fathers, 1, 1, 'holiday'),
            $this->window('Independence Day Sale', Carbon::create($year, 7, 4), 4, 3, 'holiday'),
            $this->window('Labor Day Sale', $labor, 4, 2, 'holiday'),
            $this->window('Columbus Day / Indigenous Peoples Day Sale', $columbus, 3, 3, 'holiday'),
            $this->window('Halloween Sale', Carbon::create($year, 10, 31), 5, 1, 'holiday'),
            $this->window('Veterans Day Sale', Carbon::create($year, 11, 11), 3, 2, 'holiday'),
            $this->window('Thanksgiving Sale', $thanksgiving, 3, 0, 'holiday'),
            $this->window('Black Friday / Cyber Monday Sale', $thanksgiving->copy()->addDay(), 0, 5, 'holiday'),
            $this->window('Christmas Sale', Carbon::create($year, 12, 25), 10, 0, 'holiday'),
            $this->window("New Year's Eve Sale", Carbon::create($year, 12, 26), 0, 5, 'holiday'),
        ];

        if ($year % 4 === 1) {
            // Inauguration Day — Jan 20 following presidential elections.
            array_splice($anchors, 1, 0, [
                $this->window('Inauguration Day Sale', Carbon::create($year, 1, 20), 2, 2, 'holiday'),
            ]);
        }

        return $anchors;
    }

    /**
     * @param list<array{title: string, start: Carbon, end: Carbon, kind: string}> $periods
     * @return list<array{title: string, start: Carbon, end: Carbon, kind: string}>
     */
    private function mergeOverlaps(array $periods): array
    {
        if ($periods === []) {
            return [];
        }

        $result = [];
        $current = $periods[0];
        for ($i = 1, $n = count($periods); $i < $n; $i++) {
            $next = $periods[$i];
            if ($next['start']->lte($current['end'])) {
                // Prefer the later holiday title when windows collide.
                if ($next['start']->gte($current['start'])) {
                    $current['title'] = $next['title'];
                    $current['kind'] = $next['kind'];
                }
                if ($next['end']->gt($current['end'])) {
                    $current['end'] = $next['end']->copy();
                }
            } else {
                $result[] = $current;
                $current = $next;
            }
        }
        $result[] = $current;

        return $result;
    }

    /**
     * @param list<array{title: string, start: Carbon, end: Carbon, kind: string}> $periods
     * @return list<array{title: string, start: Carbon, end: Carbon, kind: string}>
     */
    private function fillYearGaps(array $periods, int $year): array
    {
        $yearStart = Carbon::create($year, 1, 1)->startOfDay();
        $yearEnd = Carbon::create($year, 12, 31)->startOfDay();
        $filled = [];
        $cursor = $yearStart->copy();

        foreach ($periods as $period) {
            $start = $period['start']->copy()->max($yearStart);
            $end = $period['end']->copy()->min($yearEnd);
            if ($end->lt($start)) {
                continue;
            }
            if ($cursor->lt($start)) {
                $gapEnd = $start->copy()->subDay();
                if ($gapEnd->gte($cursor)) {
                    $filled[] = [
                        'title' => $this->seasonalTitle($cursor),
                        'start' => $cursor->copy(),
                        'end' => $gapEnd,
                        'kind' => 'seasonal',
                    ];
                }
            }
            $filled[] = [
                'title' => $period['title'],
                'start' => $start,
                'end' => $end,
                'kind' => $period['kind'],
            ];
            $cursor = $end->copy()->addDay();
        }

        if ($cursor->lte($yearEnd)) {
            $filled[] = [
                'title' => $this->seasonalTitle($cursor),
                'start' => $cursor->copy(),
                'end' => $yearEnd->copy(),
                'kind' => 'seasonal',
            ];
        }

        return $filled;
    }

    private function seasonalTitle(Carbon $date): string
    {
        return match ((int) $date->month) {
            12, 1, 2 => 'Winter Window & Door Sale',
            3, 4, 5 => 'Spring Replacement Sale',
            6, 7, 8 => 'Summer Sale',
            default => 'Fall Savings Event',
        };
    }

    /**
     * @return array{title: string, start: Carbon, end: Carbon, kind: string}
     */
    private function window(string $title, Carbon $anchor, int $before, int $after, string $kind): array
    {
        $start = $anchor->copy()->subDays($before)->startOfDay();
        $end = $anchor->copy()->addDays($after)->startOfDay();
        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        return [
            'title' => $title,
            'start' => $start,
            'end' => $end,
            'kind' => $kind,
        ];
    }

    private function nthWeekday(int $year, int $month, int $weekday, int $nth): Carbon
    {
        $date = Carbon::create($year, $month, 1)->startOfDay();
        while ($date->dayOfWeek !== $weekday) {
            $date->addDay();
        }
        $date->addWeeks($nth - 1);

        return $date;
    }

    private function lastWeekday(int $year, int $month, int $weekday): Carbon
    {
        $date = Carbon::create($year, $month, 1)->endOfMonth()->startOfDay();
        while ($date->dayOfWeek !== $weekday) {
            $date->subDay();
        }

        return $date;
    }

    private function easterSunday(int $year): Carbon
    {
        // Anonymous Gregorian algorithm.
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($year, $month, $day)->startOfDay();
    }

    private function syncLegacyGlobalSettings(?string $endDate, string $promotionName): void
    {
        $item = GlobalSettingsWebflowItem::query()
            ->where('is_archived', false)
            ->where('is_draft', false)
            ->where(function ($query) {
                $query->where('field_data->slug', 'default')
                    ->orWhereNull('field_data->slug');
            })
            ->orderBy('id')
            ->first();

        if (! $item) {
            return;
        }

        $fieldData = is_array($item->field_data) ? $item->field_data : [];
        $fieldData['end-date'] = $endDate ?? '';
        $fieldData['promotion-name'] = $promotionName;
        $item->field_data = $fieldData;
        $item->wf_end_date = $endDate;
        $item->wf_promotion_name = $promotionName;
        $item->save();
    }
}
