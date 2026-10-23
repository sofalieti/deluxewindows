<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Analytics;

use App\Services\CallAnalyticsService;
use App\Services\ScreenSizeStatsService;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\DateRange;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Repository;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;

class ScreenSizeAnalyticsScreen extends Screen
{
    private string $periodLabel = '';

    public function __construct(
        private readonly CallAnalyticsService $periods,
        private readonly ScreenSizeStatsService $stats,
    ) {
    }

    public function query(Request $request): iterable
    {
        $preset = (string) $request->input('filter.preset', CallAnalyticsService::PRESET_LAST_30);
        $range = $request->input('filter.range');
        $range = is_array($range) ? $range : null;

        $period = $this->periods->resolvePeriod($preset, $range);
        $this->periodLabel = $period['label'];
        $rows = $this->stats->rows($period['start'], $period['end']);

        return [
            'filter' => [
                'preset' => $period['preset'],
                'range' => [
                    'start' => $period['start']->format('Y-m-d'),
                    'end' => $period['end']->subSecond()->format('Y-m-d'),
                ],
            ],
            'rows' => collect($rows)->map(fn (array $row) => new Repository($row)),
        ];
    }

    public function name(): ?string
    {
        return 'Screen sizes';
    }

    public function description(): ?string
    {
        return 'First-visit screen width, in 50px steps. Lead and connected-call rates use visits in the same step. Period: '.$this->periodLabel.'.';
    }

    public function permission(): ?iterable
    {
        return ['platform.analytics'];
    }

    public function commandBar(): iterable
    {
        return [];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Select::make('filter.preset')
                    ->title('Period')
                    ->options($this->periods->presetOptions()),

                DateRange::make('filter.range')
                    ->title('Custom dates')
                    ->help('Used when Period is set to Custom range.'),

                Button::make('Apply')
                    ->method('apply')
                    ->type(Color::PRIMARY)
                    ->icon('bs.funnel'),
            ])->title('Filters'),

            Layout::table('rows', [
                TD::make('label', 'Width')
                    ->render(fn (Repository $row) => e((string) $row->get('label'))),
                TD::make('visits', 'Visits')
                    ->align(TD::ALIGN_RIGHT)
                    ->render(fn (Repository $row) => number_format((int) $row->get('visits'))),
                TD::make('leads', 'Leads')
                    ->align(TD::ALIGN_RIGHT)
                    ->render(fn (Repository $row) => number_format((int) $row->get('leads'))),
                TD::make('lead_rate', 'Lead rate')
                    ->align(TD::ALIGN_RIGHT)
                    ->render(fn (Repository $row) => $this->percent($row->get('lead_rate'))),
                TD::make('calls', 'Connected calls')
                    ->align(TD::ALIGN_RIGHT)
                    ->render(fn (Repository $row) => number_format((int) $row->get('calls'))),
                TD::make('call_rate', 'Call rate')
                    ->align(TD::ALIGN_RIGHT)
                    ->render(fn (Repository $row) => $this->percent($row->get('call_rate'))),
            ]),
        ];
    }

    public function apply(): void
    {
        // Reloads query from request input.
    }

    private function percent(mixed $value): string
    {
        return $value === null ? '—' : number_format((float) $value, 1).'%';
    }
}
