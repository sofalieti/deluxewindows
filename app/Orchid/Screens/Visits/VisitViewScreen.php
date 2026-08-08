<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Visits;

use App\Models\SiteVisit;
use App\Services\ServiceAreaRegions;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\Sight;
use Orchid\Support\Facades\Layout;

class VisitViewScreen extends Screen
{
    public ?SiteVisit $visit = null;

    public function query(SiteVisit $visit): iterable
    {
        $this->visit = $visit;

        return [
            'visit' => $visit,
        ];
    }

    public function name(): ?string
    {
        return $this->visit
            ? 'Visit #'.$this->visit->id
            : 'Visit';
    }

    public function description(): ?string
    {
        return 'Session visit attribution (source, city, click ids).';
    }

    public function permission(): ?iterable
    {
        return ['platform.visits'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make('Back to list')
                ->icon('bs.arrow-left')
                ->route('platform.visits'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::columns([
                Layout::legend('visit', [
                    Sight::make('created_at', 'Visited at')
                        ->render(function (SiteVisit $visit): string {
                            if (! $visit->created_at) {
                                return '-';
                            }

                            return e($visit->created_at
                                ->copy()
                                ->timezone('America/Los_Angeles')
                                ->format('Y-m-d H:i:s')).' PT';
                        }),
                    Sight::make('traffic_source', 'Traffic')
                        ->render(fn (SiteVisit $visit) => '<span class="badge bg-'
                            .$visit->trafficSourceColor().' text-white">'
                            .e($visit->trafficSourceLabel()).'</span>'
                            .'<div class="small text-muted mt-1">'
                            .e($visit->trafficSourceDetail() ?: '-').'</div>'),
                    Sight::make('page_url', 'Page URL')
                        ->render(function (SiteVisit $visit): string {
                            $url = trim((string) ($visit->page_url ?? ''));
                            if ($url === '') {
                                return '-';
                            }

                            return '<a href="'.e($url).'" target="_blank" rel="noopener">'.e($url).'</a>';
                        }),
                    Sight::make('landing_page', 'Last landing page')
                        ->render(fn (SiteVisit $visit) => e((string) ($visit->landing_page ?: '-'))),
                    Sight::make('first_landing_page', 'First landing page')
                        ->render(fn (SiteVisit $visit) => e((string) ($visit->first_landing_page ?: '-'))),
                    Sight::make('referrer', 'Last referrer')
                        ->render(fn (SiteVisit $visit) => e((string) ($visit->referrer ?: '-'))),
                    Sight::make('first_referrer', 'First referrer')
                        ->render(fn (SiteVisit $visit) => e((string) ($visit->first_referrer ?: '-'))),
                    Sight::make('geo_location', 'Geo')
                        ->render(fn (SiteVisit $visit) => e((string) ($visit->geo_location ?: '-'))),
                    Sight::make('ip_address', 'IP')
                        ->render(fn (SiteVisit $visit) => e((string) ($visit->ip_address ?: '-'))),
                    Sight::make('user_agent', 'User agent')
                        ->render(fn (SiteVisit $visit) => e((string) ($visit->user_agent ?: '-'))),
                ]),

                Layout::legend('visit', [
                    Sight::make('utm_source', 'Last UTM source')
                        ->render(fn (SiteVisit $visit) => e((string) ($visit->utm_source ?: '-'))),
                    Sight::make('utm_medium', 'Last UTM medium')
                        ->render(fn (SiteVisit $visit) => e((string) ($visit->utm_medium ?: '-'))),
                    Sight::make('utm_campaign', 'Last UTM campaign')
                        ->render(fn (SiteVisit $visit) => e((string) ($visit->utm_campaign ?: '-'))),
                    Sight::make('gclid', 'Last GCLID')
                        ->render(fn (SiteVisit $visit) => e((string) ($visit->gclid ?: '-'))),
                    Sight::make('first_utm_source', 'First UTM source')
                        ->render(fn (SiteVisit $visit) => e((string) ($visit->first_utm_source ?: '-'))),
                    Sight::make('first_utm_medium', 'First UTM medium')
                        ->render(fn (SiteVisit $visit) => e((string) ($visit->first_utm_medium ?: '-'))),
                    Sight::make('first_utm_campaign', 'First UTM campaign')
                        ->render(fn (SiteVisit $visit) => e((string) ($visit->first_utm_campaign ?: '-'))),
                    Sight::make('first_gclid', 'First GCLID')
                        ->render(fn (SiteVisit $visit) => e((string) ($visit->first_gclid ?: '-'))),
                    Sight::make('utm_content', 'UTM content')
                        ->render(fn (SiteVisit $visit) => e((string) ($visit->utm_content ?: '-'))),
                    Sight::make('utm_term', 'UTM term')
                        ->render(fn (SiteVisit $visit) => e((string) ($visit->utm_term ?: '-'))),
                    Sight::make('utm_city', 'UTM city')
                        ->render(function (SiteVisit $visit) {
                            $regions = app(ServiceAreaRegions::class);

                            return e($regions->utmCityLabel(
                                $visit->utm_city,
                                $regions->platformFromAttribution([
                                    'utm_source' => $visit->utm_source,
                                    'gclid' => $visit->gclid,
                                    'msclkid' => $visit->msclkid,
                                    'first_utm_source' => $visit->first_utm_source,
                                    'first_gclid' => $visit->first_gclid,
                                    'first_msclkid' => $visit->first_msclkid,
                                ])
                            ));
                        }),
                    Sight::make('first_utm_city', 'First UTM city')
                        ->render(function (SiteVisit $visit) {
                            $regions = app(ServiceAreaRegions::class);

                            return e($regions->utmCityLabel(
                                $visit->first_utm_city,
                                $regions->platformFromAttribution([
                                    'utm_source' => $visit->first_utm_source,
                                    'gclid' => $visit->first_gclid,
                                    'msclkid' => $visit->first_msclkid,
                                ])
                            ));
                        }),
                    Sight::make('utm_redirect', 'UTM redirect')
                        ->render(fn (SiteVisit $visit) => e((string) ($visit->utm_redirect ?: '-'))),
                    Sight::make('matchtype', 'Match type')
                        ->render(fn (SiteVisit $visit) => e((string) ($visit->matchtype ?: '-'))),
                    Sight::make('device', 'Device')
                        ->render(fn (SiteVisit $visit) => e((string) ($visit->device ?: '-'))),
                    Sight::make('creative', 'Creative')
                        ->render(fn (SiteVisit $visit) => e((string) ($visit->creative ?: '-'))),
                    Sight::make('fbclid', 'FBCLID')
                        ->render(fn (SiteVisit $visit) => e((string) ($visit->fbclid ?: '-'))),
                    Sight::make('msclkid', 'MSCLKID')
                        ->render(fn (SiteVisit $visit) => e((string) ($visit->msclkid ?: '-'))),
                ]),
            ]),
        ];
    }
}
