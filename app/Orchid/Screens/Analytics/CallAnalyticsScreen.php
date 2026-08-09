<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Analytics;

use App\Orchid\Layouts\Analytics\CallsDailyChart;
use App\Orchid\Layouts\Analytics\NewReturningChart;
use App\Orchid\Layouts\Analytics\TrafficSourceChart;
use App\Services\CallAnalyticsService;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\DateRange;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Repository;
use Orchid\Screen\Screen;
use Orchid\Screen\Sight;
use Orchid\Screen\TD;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;

class CallAnalyticsScreen extends Screen
{
    private string $periodLabel = '';

    public function __construct(
        private readonly CallAnalyticsService $analytics,
    ) {
    }

    public function query(Request $request): iterable
    {
        $preset = (string) $request->input('filter.preset', CallAnalyticsService::PRESET_LAST_7);
        $range = $request->input('filter.range');
        $range = is_array($range) ? $range : null;
        $mainLinesOnly = $request->boolean('filter.main_lines_only', true);

        $period = $this->analytics->resolvePeriod($preset, $range);
        $this->periodLabel = $period['label'];

        $data = $this->analytics->build(
            $period['start'],
            $period['end'],
            $period['previousStart'],
            $period['previousEnd'],
            $mainLinesOnly,
        );

        return [
            'filter' => [
                'preset' => $period['preset'],
                'range' => [
                    'start' => $period['start']->format('Y-m-d'),
                    'end' => $period['end']->subSecond()->format('Y-m-d'),
                ],
                'main_lines_only' => $mainLinesOnly,
            ],
            'metrics' => $data['kpis'],
            'daily_chart' => $data['daily_chart'],
            'new_returning_chart' => $data['new_returning_chart'],
            'source_chart' => $data['source_chart'],
            'top_cities' => collect($data['top_cities'])->map(fn (array $row) => new Repository($row)),
            'top_campaigns' => collect($data['top_campaigns'])->map(fn (array $row) => new Repository($row)),
            'by_line' => collect($data['by_line'])->map(fn (array $row) => new Repository($row)),
            'heatmap' => $data['missed_heatmap'],
            'funnel' => $data['funnel'],
            'transcripts' => $data['transcripts'],
            'click_quality' => new Repository($data['click_quality']),
            'summary' => $data['summary'],
        ];
    }

    public function name(): ?string
    {
        return 'Call Analytics';
    }

    public function description(): ?string
    {
        return 'Call volume, new callers, sources, and transcript insights. Period: '.$this->periodLabel.'.';
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
            Layout::view('admin.call-analytics.assets'),

            Layout::rows([
                Select::make('filter.preset')
                    ->title('Period')
                    ->options($this->analytics->presetOptions()),

                DateRange::make('filter.range')
                    ->title('Custom dates')
                    ->help('Used when Period is set to Custom range.'),

                CheckBox::make('filter.main_lines_only')
                    ->title('Lines')
                    ->placeholder('Main numbers only')
                    ->sendTrueOrFalse()
                    ->help('When checked, only monitored RingCentral business lines are included.'),

                Button::make('Apply')
                    ->method('apply')
                    ->type(Color::PRIMARY)
                    ->icon('bs.funnel'),
            ])->title('Filters'),

            Layout::metrics([
                'Total calls' => 'metrics.total_calls',
                'New callers' => 'metrics.new_callers',
                'Connected rate' => 'metrics.connected_rate',
                'Missed' => 'metrics.missed',
                'Avg duration' => 'metrics.avg_duration',
                'Paid ads calls' => 'metrics.paid_ads_calls',
            ]),

            CallsDailyChart::make('daily_chart', 'Calls by day')
                ->description('All calls, inbound, and connected over the selected period (Pacific Time).'),

            Layout::columns([
                NewReturningChart::make('new_returning_chart', 'New vs returning callers')
                    ->description('Distinct callers per day. New = first-ever call that day.'),

                TrafficSourceChart::make('source_chart', 'Confirmed calls by source')
                    ->description('Phone clicks matched to a RingCentral call, by traffic source.'),
            ]),

            Layout::columns([
                Layout::block([
                    Layout::table('top_cities', [
                        TD::make('city', 'City')->cantHide(),
                        TD::make('calls', 'Calls')
                            ->align(TD::ALIGN_RIGHT)
                            ->render(fn (Repository $row) => number_format((int) $row->get('calls'))),
                    ]),
                ])->title('Top cities'),

                Layout::block([
                    Layout::table('top_campaigns', [
                        TD::make('campaign', 'Campaign')->cantHide(),
                        TD::make('calls', 'Calls')
                            ->align(TD::ALIGN_RIGHT)
                            ->render(fn (Repository $row) => number_format((int) $row->get('calls'))),
                    ]),
                ])->title('Top campaigns'),
            ]),

            Layout::columns([
                Layout::block([
                    Layout::table('by_line', [
                        TD::make('line', 'Business line')->cantHide(),
                        TD::make('calls', 'Calls')
                            ->align(TD::ALIGN_RIGHT)
                            ->render(fn (Repository $row) => number_format((int) $row->get('calls'))),
                        TD::make('connected', 'Connected')
                            ->align(TD::ALIGN_RIGHT)
                            ->render(fn (Repository $row) => number_format((int) $row->get('connected'))),
                    ]),
                ])->title('By line'),

                Layout::block([
                    Layout::view('admin.partials.call-analytics-funnel'),
                ])->title('Funnel'),
            ]),

            Layout::block([
                Layout::view('admin.partials.call-analytics-heatmap'),
            ])->title('Missed / voicemail by hour (PT)'),

            Layout::columns([
                Layout::block([
                    Layout::view('admin.partials.call-analytics-transcripts'),
                ])->title('Transcript insights'),

                Layout::block([
                    Layout::legend('click_quality', [
                        Sight::make('total', 'Phone clicks'),
                        Sight::make('confirmed', 'Confirmed calls'),
                        Sight::make('confirm_rate', 'Confirm rate')
                            ->render(fn (Repository $repo) => number_format((float) $repo->get('confirm_rate'), 1).'%'),
                        Sight::make('spam', 'Spam clicks'),
                        Sight::make('spam_rate', 'Spam rate')
                            ->render(fn (Repository $repo) => number_format((float) $repo->get('spam_rate'), 1).'%'),
                    ]),
                ])->title('Phone click quality'),
            ]),
        ];
    }

    public function apply(): void
    {
        // Reloads query from request input.
    }
}
