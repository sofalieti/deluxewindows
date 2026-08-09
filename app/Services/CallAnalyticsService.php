<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\PhoneClick;
use App\Models\RingCentralCall;
use App\Models\SiteVisit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class CallAnalyticsService
{
    public const TIMEZONE = 'America/Los_Angeles';

    public const PRESET_LAST_7 = 'last_7_days';

    public const PRESET_LAST_30 = 'last_30_days';

    public const PRESET_THIS_WEEK = 'this_week';

    public const PRESET_THIS_MONTH = 'this_month';

    public const PRESET_CUSTOM = 'custom';

    public function __construct(
        private readonly RingCentralPhoneCallStatsService $callStats,
        private readonly ServiceAreaRegions $regions,
        private readonly PromotionControlService $promotions,
        private readonly RingCentralCallLogService $callLog,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function presetOptions(): array
    {
        return [
            self::PRESET_LAST_7 => 'Last 7 days',
            self::PRESET_LAST_30 => 'Last 30 days',
            self::PRESET_THIS_WEEK => 'This week',
            self::PRESET_THIS_MONTH => 'This month',
            self::PRESET_CUSTOM => 'Custom range',
        ];
    }

    /**
     * @param  array{start?: mixed, end?: mixed}|null  $customRange
     * @return array{
     *     preset: string,
     *     start: CarbonImmutable,
     *     end: CarbonImmutable,
     *     previousStart: CarbonImmutable,
     *     previousEnd: CarbonImmutable,
     *     label: string
     * }
     */
    public function resolvePeriod(string $preset, ?array $customRange = null): array
    {
        $now = CarbonImmutable::now(self::TIMEZONE);
        $preset = array_key_exists($preset, $this->presetOptions()) ? $preset : self::PRESET_LAST_7;

        if ($preset === self::PRESET_CUSTOM) {
            $startRaw = trim((string) ($customRange['start'] ?? ''));
            $endRaw = trim((string) ($customRange['end'] ?? ''));
            if ($startRaw !== '' && $endRaw !== '') {
                $start = CarbonImmutable::parse($startRaw, self::TIMEZONE)->startOfDay();
                $end = CarbonImmutable::parse($endRaw, self::TIMEZONE)->endOfDay()->addSecond()->startOfSecond();
                if ($end->lessThanOrEqualTo($start)) {
                    $end = $start->addDay();
                }
            } else {
                $preset = self::PRESET_LAST_7;
                $start = $now->subDays(6)->startOfDay();
                $end = $now->addDay()->startOfDay();
            }
        } else {
            [$start, $end] = match ($preset) {
                self::PRESET_LAST_30 => [$now->subDays(29)->startOfDay(), $now->addDay()->startOfDay()],
                self::PRESET_THIS_WEEK => [$now->startOfWeek(CarbonImmutable::MONDAY), $now->addDay()->startOfDay()],
                self::PRESET_THIS_MONTH => [$now->startOfMonth(), $now->addDay()->startOfDay()],
                default => [$now->subDays(6)->startOfDay(), $now->addDay()->startOfDay()],
            };
        }

        $seconds = max(1, $end->getTimestamp() - $start->getTimestamp());
        $previousEnd = $start;
        $previousStart = $previousEnd->subSeconds($seconds);

        $label = $start->format('M j, Y').' – '.$end->subSecond()->format('M j, Y').' PT';

        return [
            'preset' => $preset,
            'start' => $start,
            'end' => $end,
            'previousStart' => $previousStart,
            'previousEnd' => $previousEnd,
            'label' => $label,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function build(
        CarbonImmutable $start,
        CarbonImmutable $end,
        CarbonImmutable $previousStart,
        CarbonImmutable $previousEnd,
        bool $mainLinesOnly = true,
    ): array {
        $cacheKey = sprintf(
            'call-analytics:v1:%s:%s:%s:%s:%d',
            $start->toIso8601String(),
            $end->toIso8601String(),
            $previousStart->toIso8601String(),
            $previousEnd->toIso8601String(),
            $mainLinesOnly ? 1 : 0,
        );

        return Cache::remember($cacheKey, 600, function () use (
            $start,
            $end,
            $previousStart,
            $previousEnd,
            $mainLinesOnly,
        ): array {
            return $this->compute($start, $end, $previousStart, $previousEnd, $mainLinesOnly);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function compute(
        CarbonImmutable $start,
        CarbonImmutable $end,
        CarbonImmutable $previousStart,
        CarbonImmutable $previousEnd,
        bool $mainLinesOnly = true,
    ): array {
        if (! Schema::hasTable('ringcentral_calls')) {
            return $this->emptyPayload($start, $end);
        }

        $monitored = $this->monitoredPhones();
        $currentCalls = $this->loadCalls($start, $end, $mainLinesOnly, $monitored);
        $previousCalls = $this->loadCalls($previousStart, $previousEnd, $mainLinesOnly, $monitored);

        $currentStats = $this->summarizeCalls($currentCalls);
        $previousStats = $this->summarizeCalls($previousCalls);

        $firstCallAtByPhone = $this->firstCallAtByPhone(
            $currentCalls->pluck('external_phone')
                ->merge($previousCalls->pluck('external_phone'))
                ->filter()
                ->unique()
                ->values()
                ->all()
        );

        $newReturning = $this->newVsReturning($currentCalls, $firstCallAtByPhone, $start, $end);
        $prevNewReturning = $this->newVsReturning($previousCalls, $firstCallAtByPhone, $previousStart, $previousEnd);

        $daily = $this->dailySeries($currentCalls, $firstCallAtByPhone, $start, $end);
        $bySource = $this->bySource($start, $end);
        $prevBySource = $this->bySource($previousStart, $previousEnd);
        $byCity = $this->byCity($start, $end);
        $byCampaign = $this->byCampaign($start, $end);
        $byLine = $this->byLine($currentCalls);
        $missedHeatmap = $this->missedHeatmap($currentCalls);
        $funnel = $this->funnel($start, $end, $currentStats);
        $transcripts = $this->transcriptInsights($currentCalls);
        $clickQuality = $this->clickQuality($start, $end);

        $paidAdsCalls = $this->paidAdsCount($bySource['counts']);
        $prevPaidAdsCalls = $this->paidAdsCount($prevBySource['counts']);

        return [
            'kpis' => [
                'total_calls' => $this->metric(
                    $currentStats['total'],
                    $previousStats['total']
                ),
                'new_callers' => $this->metric(
                    $newReturning['new'],
                    $prevNewReturning['new']
                ),
                'connected_rate' => $this->metricPercent(
                    $currentStats['connected_rate'],
                    $previousStats['connected_rate']
                ),
                'missed' => $this->metric(
                    $currentStats['missed'],
                    $previousStats['missed']
                ),
                'avg_duration' => [
                    'value' => $this->formatDuration($currentStats['avg_duration']),
                    'diff' => $this->percentDiff(
                        $currentStats['avg_duration'],
                        $previousStats['avg_duration']
                    ),
                ],
                'paid_ads_calls' => $this->metric($paidAdsCalls, $prevPaidAdsCalls),
            ],
            'summary' => [
                'inbound' => $currentStats['inbound'],
                'outbound' => $currentStats['outbound'],
                'connected' => $currentStats['connected'],
                'missed' => $currentStats['missed'],
                'voicemail' => $currentStats['voicemail'],
                'total_duration' => $currentStats['total_duration'],
                'returning_callers' => $newReturning['returning'],
                'new_callers' => $newReturning['new'],
                'returning_share' => $newReturning['returning_share'],
            ],
            'daily_chart' => $daily['chart'],
            'new_returning_chart' => $daily['new_returning_chart'],
            'source_chart' => $bySource['chart'],
            'top_cities' => $byCity,
            'top_campaigns' => $byCampaign,
            'by_line' => $byLine,
            'missed_heatmap' => $missedHeatmap,
            'funnel' => $funnel,
            'transcripts' => $transcripts,
            'click_quality' => $clickQuality,
            'period' => [
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public function monitoredPhones(): array
    {
        return array_values(array_filter(
            array_map(
                fn (string $phone): string => $this->callLog->normalizePhone($phone),
                $this->promotions->ringCentralPhones(),
            ),
            fn (string $phone): bool => $phone !== '',
        ));
    }

    /**
     * @param  list<string>  $monitored
     * @return Collection<int, RingCentralCall>
     */
    private function loadCalls(
        CarbonImmutable $start,
        CarbonImmutable $end,
        bool $mainLinesOnly,
        array $monitored,
    ): Collection {
        $startUtc = $start->setTimezone('UTC');
        $endUtc = $end->setTimezone('UTC');

        $query = RingCentralCall::query()
            ->visible()
            ->where('started_at', '>=', $startUtc->format('Y-m-d H:i:s'))
            ->where('started_at', '<', $endUtc->format('Y-m-d H:i:s'))
            ->orderBy('started_at');

        if ($mainLinesOnly && $monitored !== []) {
            $query->onMonitoredLines($monitored);
        }

        return $query->get([
            'id',
            'ringcentral_call_id',
            'direction',
            'result',
            'started_at',
            'duration',
            'business_phone',
            'external_phone',
            'contact_id',
            'transcript_status',
            'transcript_summary',
        ]);
    }

    /**
     * @param  Collection<int, RingCentralCall>  $calls
     * @return array{
     *     total: int,
     *     inbound: int,
     *     outbound: int,
     *     connected: int,
     *     missed: int,
     *     voicemail: int,
     *     connected_rate: float,
     *     avg_duration: float,
     *     total_duration: int
     * }
     */
    private function summarizeCalls(Collection $calls): array
    {
        $total = $calls->count();
        $inbound = 0;
        $outbound = 0;
        $connected = 0;
        $missed = 0;
        $voicemail = 0;
        $totalDuration = 0;

        foreach ($calls as $call) {
            $direction = strtolower((string) $call->direction);
            if ($direction === 'inbound') {
                $inbound++;
            } elseif ($direction === 'outbound') {
                $outbound++;
            }

            if ($this->callStats->isConnectedCall($call)) {
                $connected++;
            }

            $result = strtolower(trim((string) ($call->result ?? '')));
            if (str_contains($result, 'missed')) {
                $missed++;
            }
            if (str_contains($result, 'voicemail')) {
                $voicemail++;
            }

            $totalDuration += max(0, (int) $call->duration);
        }

        return [
            'total' => $total,
            'inbound' => $inbound,
            'outbound' => $outbound,
            'connected' => $connected,
            'missed' => $missed,
            'voicemail' => $voicemail,
            'connected_rate' => $total > 0 ? round(($connected / $total) * 100, 1) : 0.0,
            'avg_duration' => $total > 0 ? round($totalDuration / $total, 1) : 0.0,
            'total_duration' => $totalDuration,
        ];
    }

    /**
     * @param  list<string|null>  $phones
     * @return array<string, CarbonImmutable>
     */
    private function firstCallAtByPhone(array $phones): array
    {
        $keys = [];
        foreach ($phones as $phone) {
            $key = $this->callStats->phoneKey($phone);
            if ($key !== null) {
                $keys[$key] = true;
            }
        }

        if ($keys === []) {
            return [];
        }

        $last10List = array_keys($keys);
        $rows = RingCentralCall::query()
            ->visible()
            ->where(function ($query) use ($last10List): void {
                foreach ($last10List as $index => $last10) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $query->{$method}('external_phone', 'like', '%'.$last10);
                }
            })
            ->select(['external_phone', 'started_at'])
            ->orderBy('started_at')
            ->get();

        $first = [];
        foreach ($rows as $row) {
            $key = $this->callStats->phoneKey((string) $row->external_phone);
            if ($key === null || isset($first[$key])) {
                continue;
            }
            $at = $row->started_at;
            if ($at instanceof CarbonImmutable) {
                $first[$key] = $at;
            }
        }

        return $first;
    }

    /**
     * @param  Collection<int, RingCentralCall>  $calls
     * @param  array<string, CarbonImmutable>  $firstCallAtByPhone
     * @return array{new: int, returning: int, returning_share: float}
     */
    private function newVsReturning(
        Collection $calls,
        array $firstCallAtByPhone,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): array {
        $newPhones = [];
        $returningPhones = [];

        foreach ($calls as $call) {
            $key = $this->callStats->phoneKey((string) $call->external_phone);
            if ($key === null) {
                continue;
            }

            $firstAt = $firstCallAtByPhone[$key] ?? $call->started_at;
            if (! $firstAt instanceof CarbonImmutable) {
                continue;
            }

            $firstPt = $firstAt->setTimezone(self::TIMEZONE);
            if ($firstPt->greaterThanOrEqualTo($start) && $firstPt->lessThan($end)) {
                $newPhones[$key] = true;
            } else {
                $returningPhones[$key] = true;
            }
        }

        // A phone counted as new should not also count as returning in the same period.
        foreach ($newPhones as $key => $_) {
            unset($returningPhones[$key]);
        }

        $new = count($newPhones);
        $returning = count($returningPhones);
        $total = $new + $returning;

        return [
            'new' => $new,
            'returning' => $returning,
            'returning_share' => $total > 0 ? round(($returning / $total) * 100, 1) : 0.0,
        ];
    }

    /**
     * @param  Collection<int, RingCentralCall>  $calls
     * @param  array<string, CarbonImmutable>  $firstCallAtByPhone
     * @return array{chart: list<array{name: string, values: list<int>, labels: list<string>}>, new_returning_chart: list<array{name: string, values: list<int>, labels: list<string>}>}
     */
    private function dailySeries(
        Collection $calls,
        array $firstCallAtByPhone,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): array {
        $labels = [];
        $all = [];
        $inbound = [];
        $connected = [];
        $new = [];
        $returning = [];

        for ($day = $start->startOfDay(); $day->lessThan($end); $day = $day->addDay()) {
            $key = $day->format('Y-m-d');
            $labels[] = $day->format('M j');
            $all[$key] = 0;
            $inbound[$key] = 0;
            $connected[$key] = 0;
            $new[$key] = 0;
            $returning[$key] = 0;
        }

        $seenNew = [];
        $seenReturning = [];

        foreach ($calls as $call) {
            $dayKey = $call->started_at?->setTimezone(self::TIMEZONE)->format('Y-m-d');
            if ($dayKey === null || ! array_key_exists($dayKey, $all)) {
                continue;
            }

            $all[$dayKey]++;
            if (strcasecmp((string) $call->direction, 'Inbound') === 0) {
                $inbound[$dayKey]++;
            }
            if ($this->callStats->isConnectedCall($call)) {
                $connected[$dayKey]++;
            }

            $phoneKey = $this->callStats->phoneKey((string) $call->external_phone);
            if ($phoneKey === null) {
                continue;
            }

            $firstAt = $firstCallAtByPhone[$phoneKey] ?? $call->started_at;
            $firstDay = $firstAt instanceof CarbonImmutable
                ? $firstAt->setTimezone(self::TIMEZONE)->format('Y-m-d')
                : null;

            if ($firstDay === $dayKey) {
                if (! isset($seenNew[$dayKey][$phoneKey])) {
                    $seenNew[$dayKey][$phoneKey] = true;
                    $new[$dayKey]++;
                }
            } else {
                if (! isset($seenReturning[$dayKey][$phoneKey])) {
                    $seenReturning[$dayKey][$phoneKey] = true;
                    $returning[$dayKey]++;
                }
            }
        }

        return [
            'chart' => [
                [
                    'name' => 'All calls',
                    'values' => array_values($all),
                    'labels' => $labels,
                ],
                [
                    'name' => 'Inbound',
                    'values' => array_values($inbound),
                    'labels' => $labels,
                ],
                [
                    'name' => 'Connected',
                    'values' => array_values($connected),
                    'labels' => $labels,
                ],
            ],
            'new_returning_chart' => [
                [
                    'name' => 'New callers',
                    'values' => array_values($new),
                    'labels' => $labels,
                ],
                [
                    'name' => 'Returning callers',
                    'values' => array_values($returning),
                    'labels' => $labels,
                ],
            ],
        ];
    }

    /**
     * @return array{counts: array<string, int>, chart: list<array{name: string, values: list<int>, labels: list<string>}>}
     */
    private function bySource(CarbonImmutable $start, CarbonImmutable $end): array
    {
        if (! Schema::hasTable('phone_clicks')) {
            return ['counts' => [], 'chart' => []];
        }

        $startUtc = $start->setTimezone('UTC')->format('Y-m-d H:i:s');
        $endUtc = $end->setTimezone('UTC')->format('Y-m-d H:i:s');

        $clicks = PhoneClick::query()
            ->where('ringcentral_status', PhoneClick::RINGCENTRAL_FOUND)
            ->whereNotNull('ringcentral_call_started_at')
            ->where('ringcentral_call_started_at', '>=', $startUtc)
            ->where('ringcentral_call_started_at', '<', $endUtc)
            ->where(function ($q): void {
                $q->whereNull('is_spam')->orWhere('is_spam', false);
            })
            ->get([
                'traffic_source',
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'gclid',
                'msclkid',
                'fbclid',
                'referrer',
            ]);

        $counts = [];
        foreach ($clicks as $click) {
            $key = (string) ($click->traffic_source ?: $click->trafficSourceKey());
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        arsort($counts);

        $labels = [];
        $values = [];
        foreach ($counts as $key => $count) {
            $labels[] = $this->sourceLabel($key);
            $values[] = $count;
        }

        $chart = $labels === []
            ? []
            : [[
                'name' => 'Confirmed calls',
                'values' => $values,
                'labels' => $labels,
            ]];

        return [
            'counts' => $counts,
            'chart' => $chart,
        ];
    }

    /**
     * @return list<array{city: string, calls: int}>
     */
    private function byCity(CarbonImmutable $start, CarbonImmutable $end): array
    {
        if (! Schema::hasTable('phone_clicks')) {
            return [];
        }

        $startUtc = $start->setTimezone('UTC')->format('Y-m-d H:i:s');
        $endUtc = $end->setTimezone('UTC')->format('Y-m-d H:i:s');

        $clicks = PhoneClick::query()
            ->where('ringcentral_status', PhoneClick::RINGCENTRAL_FOUND)
            ->whereNotNull('ringcentral_call_started_at')
            ->where('ringcentral_call_started_at', '>=', $startUtc)
            ->where('ringcentral_call_started_at', '<', $endUtc)
            ->where(function ($q): void {
                $q->whereNull('is_spam')->orWhere('is_spam', false);
            })
            ->whereNotNull('utm_city')
            ->where('utm_city', '!=', '')
            ->get([
                'utm_city',
                'utm_source',
                'gclid',
                'msclkid',
                'first_utm_source',
                'first_gclid',
                'first_msclkid',
            ]);

        $counts = [];
        foreach ($clicks as $click) {
            $platform = $this->regions->platformFromAttribution([
                'utm_source' => $click->utm_source,
                'gclid' => $click->gclid,
                'msclkid' => $click->msclkid,
                'first_utm_source' => $click->first_utm_source,
                'first_gclid' => $click->first_gclid,
                'first_msclkid' => $click->first_msclkid,
            ]);
            $resolved = $this->regions->resolveUtmCity($click->utm_city, $platform);
            $label = $resolved['name'] ?? (trim((string) $click->utm_city).' (unmatched)');
            $counts[$label] = ($counts[$label] ?? 0) + 1;
        }

        arsort($counts);

        $rows = [];
        foreach (array_slice($counts, 0, 10, true) as $city => $calls) {
            $rows[] = ['city' => $city, 'calls' => $calls];
        }

        return $rows;
    }

    /**
     * @return list<array{campaign: string, calls: int}>
     */
    private function byCampaign(CarbonImmutable $start, CarbonImmutable $end): array
    {
        if (! Schema::hasTable('phone_clicks')) {
            return [];
        }

        $startUtc = $start->setTimezone('UTC')->format('Y-m-d H:i:s');
        $endUtc = $end->setTimezone('UTC')->format('Y-m-d H:i:s');

        $rows = PhoneClick::query()
            ->where('ringcentral_status', PhoneClick::RINGCENTRAL_FOUND)
            ->whereNotNull('ringcentral_call_started_at')
            ->where('ringcentral_call_started_at', '>=', $startUtc)
            ->where('ringcentral_call_started_at', '<', $endUtc)
            ->where(function ($q): void {
                $q->whereNull('is_spam')->orWhere('is_spam', false);
            })
            ->whereNotNull('utm_campaign')
            ->where('utm_campaign', '!=', '')
            ->selectRaw('utm_campaign, COUNT(*) as calls')
            ->groupBy('utm_campaign')
            ->orderByDesc('calls')
            ->limit(10)
            ->get();

        return $rows->map(fn ($row): array => [
            'campaign' => (string) $row->utm_campaign,
            'calls' => (int) $row->calls,
        ])->all();
    }

    /**
     * @param  Collection<int, RingCentralCall>  $calls
     * @return list<array{line: string, calls: int, connected: int}>
     */
    private function byLine(Collection $calls): array
    {
        $stats = [];
        foreach ($calls as $call) {
            $line = trim((string) ($call->business_phone ?: 'Unknown'));
            if (! isset($stats[$line])) {
                $stats[$line] = ['line' => $line, 'calls' => 0, 'connected' => 0];
            }
            $stats[$line]['calls']++;
            if ($this->callStats->isConnectedCall($call)) {
                $stats[$line]['connected']++;
            }
        }

        usort($stats, fn (array $a, array $b): int => $b['calls'] <=> $a['calls']);

        return array_values($stats);
    }

    /**
     * @param  Collection<int, RingCentralCall>  $calls
     * @return array{headers: list<string>, rows: list<array{day: string, cells: list<array{hour: int, count: int, intensity: float}>}>}
     */
    private function missedHeatmap(Collection $calls): array
    {
        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $grid = [];
        foreach ($days as $day) {
            $grid[$day] = array_fill(0, 24, 0);
        }

        $max = 0;
        foreach ($calls as $call) {
            $result = strtolower(trim((string) ($call->result ?? '')));
            if (! str_contains($result, 'missed') && ! str_contains($result, 'voicemail')) {
                continue;
            }

            $at = $call->started_at?->setTimezone(self::TIMEZONE);
            if ($at === null) {
                continue;
            }

            $day = $days[((int) $at->dayOfWeekIso) - 1] ?? null;
            if ($day === null) {
                continue;
            }

            $hour = (int) $at->format('G');
            $grid[$day][$hour]++;
            $max = max($max, $grid[$day][$hour]);
        }

        $headers = [];
        for ($h = 0; $h < 24; $h++) {
            $headers[] = sprintf('%02d', $h);
        }

        $rows = [];
        foreach ($days as $day) {
            $cells = [];
            for ($h = 0; $h < 24; $h++) {
                $count = $grid[$day][$h];
                $cells[] = [
                    'hour' => $h,
                    'count' => $count,
                    'intensity' => $max > 0 ? round($count / $max, 2) : 0.0,
                ];
            }
            $rows[] = ['day' => $day, 'cells' => $cells];
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'max' => $max,
        ];
    }

    /**
     * @param  array{total: int, connected: int}  $callStats
     * @return array{visits: int, phone_clicks: int, confirmed_calls: int, connected: int, leads: int, contacts: int}
     */
    private function funnel(CarbonImmutable $start, CarbonImmutable $end, array $callStats): array
    {
        $startUtc = $start->setTimezone('UTC')->format('Y-m-d H:i:s');
        $endUtc = $end->setTimezone('UTC')->format('Y-m-d H:i:s');

        $visits = Schema::hasTable('site_visits')
            ? SiteVisit::query()->where('created_at', '>=', $startUtc)->where('created_at', '<', $endUtc)->count()
            : 0;

        $phoneClicks = Schema::hasTable('phone_clicks')
            ? PhoneClick::query()
                ->where('created_at', '>=', $startUtc)
                ->where('created_at', '<', $endUtc)
                ->where(function ($q): void {
                    $q->whereNull('is_spam')->orWhere('is_spam', false);
                })
                ->count()
            : 0;

        $confirmed = Schema::hasTable('phone_clicks')
            ? PhoneClick::query()
                ->where('ringcentral_status', PhoneClick::RINGCENTRAL_FOUND)
                ->whereNotNull('ringcentral_call_started_at')
                ->where('ringcentral_call_started_at', '>=', $startUtc)
                ->where('ringcentral_call_started_at', '<', $endUtc)
                ->where(function ($q): void {
                    $q->whereNull('is_spam')->orWhere('is_spam', false);
                })
                ->count()
            : 0;

        $leads = Schema::hasTable('leads')
            ? Lead::query()->where('created_at', '>=', $startUtc)->where('created_at', '<', $endUtc)->count()
            : 0;

        $contacts = Schema::hasTable('contacts')
            ? Contact::query()->where('created_at', '>=', $startUtc)->where('created_at', '<', $endUtc)->count()
            : 0;

        return [
            'visits' => $visits,
            'phone_clicks' => $phoneClicks,
            'confirmed_calls' => $confirmed,
            'connected' => $callStats['connected'],
            'leads' => $leads,
            'contacts' => $contacts,
        ];
    }

    /**
     * @param  Collection<int, RingCentralCall>  $calls
     * @return array{
     *     with_transcript: int,
     *     quote_discussed: int,
     *     quote_rate: float,
     *     appointment: int,
     *     appointment_rate: float,
     *     objections: list<array{text: string, count: int}>
     * }
     */
    private function transcriptInsights(Collection $calls): array
    {
        $withTranscript = 0;
        $quote = 0;
        $appointment = 0;
        $objections = [];

        foreach ($calls as $call) {
            if ($call->transcript_status !== RingCentralCall::TRANSCRIPT_COMPLETED) {
                continue;
            }

            $summary = is_array($call->transcript_summary) ? $call->transcript_summary : null;
            if ($summary === null) {
                continue;
            }

            $withTranscript++;

            if (! empty($summary['quote_discussed'])) {
                $quote++;
            }

            $appt = $summary['appointment'] ?? null;
            if ($appt === true || (is_array($appt) && ! empty($appt['scheduled'])) || (is_string($appt) && trim($appt) !== '')) {
                $appointment++;
            }

            foreach ((array) ($summary['objections'] ?? []) as $objection) {
                $text = trim(is_string($objection) ? $objection : (string) ($objection['text'] ?? $objection['label'] ?? ''));
                if ($text === '') {
                    continue;
                }
                $normalized = mb_strtolower($text);
                if (! isset($objections[$normalized])) {
                    $objections[$normalized] = ['text' => $text, 'count' => 0];
                }
                $objections[$normalized]['count']++;
            }
        }

        usort($objections, fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        return [
            'with_transcript' => $withTranscript,
            'quote_discussed' => $quote,
            'quote_rate' => $withTranscript > 0 ? round(($quote / $withTranscript) * 100, 1) : 0.0,
            'appointment' => $appointment,
            'appointment_rate' => $withTranscript > 0 ? round(($appointment / $withTranscript) * 100, 1) : 0.0,
            'objections' => array_values(array_slice($objections, 0, 10)),
        ];
    }

    /**
     * @return array{total: int, confirmed: int, confirm_rate: float, spam: int, spam_rate: float}
     */
    private function clickQuality(CarbonImmutable $start, CarbonImmutable $end): array
    {
        if (! Schema::hasTable('phone_clicks')) {
            return [
                'total' => 0,
                'confirmed' => 0,
                'confirm_rate' => 0.0,
                'spam' => 0,
                'spam_rate' => 0.0,
            ];
        }

        $startUtc = $start->setTimezone('UTC')->format('Y-m-d H:i:s');
        $endUtc = $end->setTimezone('UTC')->format('Y-m-d H:i:s');

        $total = PhoneClick::query()
            ->where('created_at', '>=', $startUtc)
            ->where('created_at', '<', $endUtc)
            ->count();

        $spam = PhoneClick::query()
            ->where('created_at', '>=', $startUtc)
            ->where('created_at', '<', $endUtc)
            ->where('is_spam', true)
            ->count();

        $confirmed = PhoneClick::query()
            ->where('created_at', '>=', $startUtc)
            ->where('created_at', '<', $endUtc)
            ->where('ringcentral_status', PhoneClick::RINGCENTRAL_FOUND)
            ->where(function ($q): void {
                $q->whereNull('is_spam')->orWhere('is_spam', false);
            })
            ->count();

        $nonSpam = max(0, $total - $spam);

        return [
            'total' => $total,
            'confirmed' => $confirmed,
            'confirm_rate' => $nonSpam > 0 ? round(($confirmed / $nonSpam) * 100, 1) : 0.0,
            'spam' => $spam,
            'spam_rate' => $total > 0 ? round(($spam / $total) * 100, 1) : 0.0,
        ];
    }

    /**
     * @return array{value: string, diff: float}
     */
    private function metric(int|float $current, int|float $previous): array
    {
        return [
            'value' => number_format((float) $current),
            'diff' => $this->percentDiff((float) $current, (float) $previous),
        ];
    }

    /**
     * @return array{value: string, diff: float}
     */
    private function metricPercent(float $current, float $previous): array
    {
        return [
            'value' => number_format($current, 1).'%',
            'diff' => round($current - $previous, 1),
        ];
    }

    private function percentDiff(float $current, float $previous): float
    {
        if ($previous == 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function formatDuration(float $seconds): string
    {
        $seconds = max(0, (int) round($seconds));

        return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function paidAdsCount(array $counts): int
    {
        return (int) ($counts['google_ads'] ?? 0)
            + (int) ($counts['microsoft_ads'] ?? 0)
            + (int) ($counts['meta_ads'] ?? 0)
            + (int) ($counts['paid_ads'] ?? 0);
    }

    private function sourceLabel(string $key): string
    {
        return match ($key) {
            'google_ads' => 'Google Ads',
            'microsoft_ads' => 'Microsoft Ads',
            'meta_ads' => 'Meta Ads',
            'paid_ads' => 'Paid Ads',
            'seo_google' => 'SEO · Google',
            'seo_bing' => 'SEO · Bing',
            'seo_other' => 'SEO · Other',
            'social' => 'Social',
            'email' => 'Email',
            'referral' => 'Referral',
            'campaign' => 'Campaign',
            'direct' => 'Direct',
            default => $key !== '' ? $key : 'Direct',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPayload(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $labels = [];
        for ($day = $start->startOfDay(); $day->lessThan($end); $day = $day->addDay()) {
            $labels[] = $day->format('M j');
        }
        $zeros = array_fill(0, count($labels), 0);

        return [
            'kpis' => [
                'total_calls' => ['value' => '0', 'diff' => 0.0],
                'new_callers' => ['value' => '0', 'diff' => 0.0],
                'connected_rate' => ['value' => '0.0%', 'diff' => 0.0],
                'missed' => ['value' => '0', 'diff' => 0.0],
                'avg_duration' => ['value' => '0:00', 'diff' => 0.0],
                'paid_ads_calls' => ['value' => '0', 'diff' => 0.0],
            ],
            'summary' => [
                'inbound' => 0,
                'outbound' => 0,
                'connected' => 0,
                'missed' => 0,
                'voicemail' => 0,
                'total_duration' => 0,
                'returning_callers' => 0,
                'new_callers' => 0,
                'returning_share' => 0.0,
            ],
            'daily_chart' => [
                ['name' => 'All calls', 'values' => $zeros, 'labels' => $labels],
                ['name' => 'Inbound', 'values' => $zeros, 'labels' => $labels],
                ['name' => 'Connected', 'values' => $zeros, 'labels' => $labels],
            ],
            'new_returning_chart' => [
                ['name' => 'New callers', 'values' => $zeros, 'labels' => $labels],
                ['name' => 'Returning callers', 'values' => $zeros, 'labels' => $labels],
            ],
            'source_chart' => [],
            'top_cities' => [],
            'top_campaigns' => [],
            'by_line' => [],
            'missed_heatmap' => ['headers' => [], 'rows' => [], 'max' => 0],
            'funnel' => [
                'visits' => 0,
                'phone_clicks' => 0,
                'confirmed_calls' => 0,
                'connected' => 0,
                'leads' => 0,
                'contacts' => 0,
            ],
            'transcripts' => [
                'with_transcript' => 0,
                'quote_discussed' => 0,
                'quote_rate' => 0.0,
                'appointment' => 0,
                'appointment_rate' => 0.0,
                'objections' => [],
            ],
            'click_quality' => [
                'total' => 0,
                'confirmed' => 0,
                'confirm_rate' => 0.0,
                'spam' => 0,
                'spam_rate' => 0.0,
            ],
            'period' => [
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
            ],
        ];
    }
}
