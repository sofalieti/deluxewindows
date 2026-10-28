<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Visits;

use App\Models\SiteEvent;
use App\Models\SiteVisit;
use App\Orchid\Layouts\Visits\VisitFiltersLayout;
use App\Services\ServiceAreaRegions;
use App\Services\VisitsSettingsService;
use App\Support\ClarityDashboard;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class VisitListScreen extends Screen
{
    /** @var array<string, list<SiteEvent>> */
    private array $signals = [];

    public function __construct(
        private readonly VisitsSettingsService $settings,
    ) {
    }

    public function query(): iterable
    {
        $interested = $this->interestedVisits();
        $this->signals = $this->signalsFor($interested->getCollection());

        return [
            'setting' => [
                'enabled' => $this->settings->enabled(),
                'behavior_enabled' => $this->settings->behaviorEnabled(),
            ],
            'microsoft' => $this->channelVisits(['microsoft_ads'], 'microsoft_page'),
            'google' => $this->channelVisits(['google_ads'], 'google_page'),
            'seo' => $this->channelVisits(['seo_google', 'seo_bing', 'seo_other'], 'seo_page'),
            'direct' => $this->channelVisits(['direct'], 'direct_page'),
            'visits' => SiteVisit::filters(VisitFiltersLayout::class)
                ->defaultSort('id', 'desc')
                ->paginate(50),
            'interested' => $interested,
        ];
    }

    public function name(): ?string
    {
        return 'Site visits';
    }

    public function description(): ?string
    {
        $visits = $this->settings->enabled() ? 'ON' : 'OFF';
        $behavior = $this->settings->behaviorEnabled() ? 'ON' : 'OFF';

        return 'Session visits with traffic source and utm_city. Visit tracking is '.$visits.'. Behavior events are '.$behavior.'.';
    }

    public function permission(): ?iterable
    {
        return ['platform.visits'];
    }

    public function commandBar(): iterable
    {
        return [
            Button::make('Clear all visits')
                ->icon('bs.trash')
                ->type(Color::DANGER)
                ->method('clearAll')
                ->confirm('Delete every visit and behavior event? This cannot be undone. Tracking settings stay as they are.'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                CheckBox::make('setting.enabled')
                    ->title('Record site visits')
                    ->placeholder('Enable visit tracking')
                    ->sendTrueOrFalse()
                    ->help('When off, POST /visit returns 204 and nothing is stored.'),
                CheckBox::make('setting.behavior_enabled')
                    ->title('Record behavior events')
                    ->placeholder('Enable clicks, scroll, forms, and engagement')
                    ->sendTrueOrFalse()
                    ->help('Stores a short event log per session. Mouse trails and session replay stay in Microsoft Clarity. When off, POST /t/events returns 204.'),
            ])->title('Tracking'),

            VisitFiltersLayout::class,

            Layout::rows([
                Button::make('Save tracking setting')
                    ->method('saveSettings')
                    ->type(Color::PRIMARY)
                    ->icon('bs.check-lg'),
            ]),

            Layout::tabs([
                'Microsoft' => Layout::table('microsoft', $this->visitColumns()),
                'Google' => Layout::table('google', $this->visitColumns()),
                'SEO' => Layout::table('seo', $this->visitColumns()),
                'Direct' => Layout::table('direct', $this->visitColumns()),
                'All visits' => Layout::table('visits', $this->visitColumns()),
                'Estimate & forms' => Layout::table('interested', array_merge(
                    $this->visitColumns(),
                    [$this->signalColumn(), $this->clarityColumn()]
                )),
            ]),
        ];
    }

    /**
     * @return list<TD>
     */
    private function visitColumns(): array
    {
        return [
                TD::make('created_at', 'Visit')
                    ->sort()
                    ->cantHide()
                    ->width('160px')
                    ->render(function (SiteVisit $visit): string {
                        $at = $visit->created_at
                            ? $visit->created_at->copy()->timezone('America/Los_Angeles')
                            : null;

                        $bot = $visit->is_bot
                            ? '<div class="small text-muted">bot</div>'
                            : '';

                        return $at
                            ? e($at->format('M d, Y')).'<div class="small text-muted">'.e($at->format('h:i A')).' PT</div>'.$bot
                            : '—';
                    }),

                TD::make('page_url', 'Page')
                    ->width('220px')
                    ->render(function (SiteVisit $visit): string {
                        $url = trim((string) ($visit->page_url ?? ''));
                        $landing = trim((string) ($visit->landing_page ?? ''));
                        $path = $url !== ''
                            ? ((string) parse_url($url, PHP_URL_PATH) ?: $url)
                            : $landing;

                        if ($url === '' && $landing === '') {
                            return '<span class="text-muted">—</span>';
                        }

                        if ($url !== '') {
                            return '<a href="'.e($url).'" target="_blank" rel="noopener">'
                                .e(\Illuminate\Support\Str::limit($path, 40))
                                .'</a>';
                        }

                        return e(\Illuminate\Support\Str::limit($path, 40));
                    }),

                TD::make('utm_source', 'Traffic')
                    ->width('150px')
                    ->render(function (SiteVisit $visit): string {
                        return '<span class="badge bg-'.$visit->trafficSourceColor().' text-white">'
                            .e($visit->trafficSourceLabel())
                            .'</span>'
                            .'<div class="small text-muted mt-1">'
                            .e(\Illuminate\Support\Str::limit($visit->trafficSourceDetail() ?: (string) ($visit->utm_source ?: ''), 24))
                            .'</div>';
                    }),

                TD::make('utm_city', 'City')
                    ->width('180px')
                    ->render(function (SiteVisit $visit): string {
                        $regions = app(ServiceAreaRegions::class);
                        $label = $regions->utmCityLabel(
                            $visit->utm_city,
                            $regions->platformFromAttribution([
                                'utm_source' => $visit->utm_source,
                                'gclid' => $visit->gclid,
                                'gbraid' => $visit->gbraid,
                                'wbraid' => $visit->wbraid,
                                'msclkid' => $visit->msclkid,
                                'first_utm_source' => $visit->first_utm_source,
                                'first_gclid' => $visit->first_gclid,
                                'first_gbraid' => $visit->first_gbraid,
                                'first_wbraid' => $visit->first_wbraid,
                                'first_msclkid' => $visit->first_msclkid,
                            ])
                        );

                        return e($label);
                    }),

                TD::make('gclid', 'Click IDs')
                    ->width('120px')
                    ->render(function (SiteVisit $visit): string {
                        $bits = [];
                        if (trim((string) $visit->gclid) !== '') {
                            $bits[] = 'GCLID';
                        }
                        if (trim((string) $visit->gbraid) !== '') {
                            $bits[] = 'GBRAID';
                        }
                        if (trim((string) $visit->wbraid) !== '') {
                            $bits[] = 'WBRAID';
                        }
                        if (trim((string) $visit->msclkid) !== '') {
                            $bits[] = 'MSCLKID';
                        }
                        if (trim((string) $visit->fbclid) !== '') {
                            $bits[] = 'FBCLID';
                        }

                        return $bits === []
                            ? '<span class="text-muted">—</span>'
                            : e(implode(' · ', $bits));
                    }),

                TD::make('pageviews', 'Pages')
                    ->width('70px')
                    ->render(fn (SiteVisit $visit) => $visit->pageviews
                        ? e((string) $visit->pageviews)
                        : '<span class="text-muted">—</span>'),

                TD::make('engaged_seconds', 'Time')
                    ->width('80px')
                    ->render(fn (SiteVisit $visit) => $visit->engaged_seconds
                        ? e($visit->engagedLabel())
                        : '<span class="text-muted">—</span>'),

                TD::make('max_scroll', 'Scroll')
                    ->width('70px')
                    ->render(fn (SiteVisit $visit) => $visit->max_scroll
                        ? e($visit->max_scroll.'%')
                        : '<span class="text-muted">—</span>'),

                TD::make('rage_clicks', 'Rage')
                    ->width('60px')
                    ->render(fn (SiteVisit $visit) => $visit->rage_clicks
                        ? e((string) $visit->rage_clicks)
                        : '<span class="text-muted">—</span>'),

                TD::make('id', 'View')
                    ->align(TD::ALIGN_CENTER)
                    ->width('70px')
                    ->render(fn (SiteVisit $visit) => Link::make('')
                        ->icon('bs.eye')
                        ->route('platform.visits.view', $visit)),
        ];
    }

    private function signalColumn(): TD
    {
        return TD::make('signal', 'Did')
            ->width('160px')
            ->render(function (SiteVisit $visit): string {
                $label = $this->signalLabel($visit);

                return $label === ''
                    ? '<span class="text-muted">—</span>'
                    : e($label);
            });
    }

    private function clarityColumn(): TD
    {
        return TD::make('clarity', 'Clarity')
            ->width('90px')
            ->render(function (SiteVisit $visit): string {
                $url = ClarityDashboard::recordingsUrl($visit->visitor_id);
                if ($url === null) {
                    return '<span class="text-muted">—</span>';
                }

                return '<a href="'.e($url).'" target="_blank" rel="noopener">Open</a>';
            });
    }

    /**
     * @param  list<string>  $sources
     */
    private function channelVisits(array $sources, string $pageName): LengthAwarePaginator
    {
        return SiteVisit::filters(VisitFiltersLayout::class)
            ->whereIn('traffic_source', $sources)
            ->defaultSort('id', 'desc')
            ->paginate(50, ['*'], $pageName);
    }

    private function interestedVisits(): LengthAwarePaginator
    {
        $query = SiteVisit::filters(VisitFiltersLayout::class)
            ->where('is_bot', false)
            ->whereNotNull('session_id');

        if (! Schema::hasTable('site_events')) {
            $query->whereRaw('1 = 0');
        } else {
            $query->whereExists(function ($events): void {
                $events->selectRaw('1')
                    ->from('site_events')
                    ->whereColumn('site_events.session_id', 'site_visits.session_id')
                    ->where(function ($match): void {
                        $match->where('site_events.type', 'form_start')
                            ->orWhere(function ($click): void {
                                $click->where('site_events.type', 'click')
                                    ->where(function ($target): void {
                                        $target->where('site_events.target', 'like', '%estimate%')
                                            ->orWhere('site_events.target', 'like', '%consultation%')
                                            ->orWhere('site_events.target', 'request-estimate');
                                    });
                            });
                    });
            });
        }

        return $query
            ->defaultSort('id', 'desc')
            ->paginate(50, ['*'], 'intent_page');
    }

    /**
     * @param  Collection<int, SiteVisit>  $visits
     * @return array<string, list<SiteEvent>>
     */
    private function signalsFor(Collection $visits): array
    {
        $sessionIds = $visits->pluck('session_id')->filter()->unique()->values();
        if ($sessionIds->isEmpty() || ! Schema::hasTable('site_events')) {
            return [];
        }

        $grouped = [];
        SiteEvent::query()
            ->whereIn('session_id', $sessionIds)
            ->where(function ($match): void {
                $match->where('type', 'form_start')
                    ->orWhere(function ($click): void {
                        $click->where('type', 'click')
                            ->where(function ($target): void {
                                $target->where('target', 'like', '%estimate%')
                                    ->orWhere('target', 'like', '%consultation%')
                                    ->orWhere('target', 'request-estimate');
                            });
                    });
            })
            ->orderBy('id')
            ->get(['session_id', 'type', 'target'])
            ->each(function (SiteEvent $event) use (&$grouped): void {
                $grouped[$event->session_id][] = $event;
            });

        return $grouped;
    }

    private function signalLabel(SiteVisit $visit): string
    {
        $events = $this->signals[$visit->session_id] ?? [];
        $bits = [];
        foreach ($events as $event) {
            if ($event->type === 'form_start') {
                $bits['form'] = 'Form started';
            } else {
                $bits['click'] = 'Estimate click';
            }
        }

        return implode(' · ', $bits);
    }

    public function saveSettings(Request $request): void
    {
        $enabled = $request->boolean('setting.enabled');
        $behavior = $request->boolean('setting.behavior_enabled');
        $this->settings->update(['enabled' => $enabled]);
        $this->settings->updateScope('behavior', ['enabled' => $behavior]);

        Toast::info('Tracking updated. Visits '.($enabled ? 'on' : 'off').', behavior '.($behavior ? 'on' : 'off').'.');
    }

    public function clearAll(): void
    {
        $events = Schema::hasTable('site_events') ? SiteEvent::query()->delete() : 0;
        $deleted = SiteVisit::query()->delete();
        Toast::warning('Cleared '.$deleted.' visit(s) and '.$events.' behavior event(s).');
    }
}
