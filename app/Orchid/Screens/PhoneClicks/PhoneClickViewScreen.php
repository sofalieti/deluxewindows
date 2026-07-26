<?php

declare(strict_types=1);

namespace App\Orchid\Screens\PhoneClicks;

use App\Models\PhoneClick;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\Sight;
use Orchid\Support\Facades\Layout;

class PhoneClickViewScreen extends Screen
{
    public ?PhoneClick $click = null;

    public function query(PhoneClick $click): iterable
    {
        $this->click = $click;

        return [
            'click' => $click,
        ];
    }

    public function name(): ?string
    {
        return $this->click
            ? 'Phone click #'.$this->click->id
            : 'Phone click';
    }

    public function description(): ?string
    {
        return 'Click-to-call details and attribution.';
    }

    public function permission(): ?iterable
    {
        return [
            'platform.leads',
        ];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make('Back to list')
                ->icon('bs.arrow-left')
                ->route('platform.phone-clicks'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::columns([
                Layout::legend('click', [
                    Sight::make('id', 'ID'),
                    Sight::make('created_at', 'Clicked')
                        ->render(fn (PhoneClick $click) => optional($click->created_at)->format('Y-m-d H:i:s')),
                    Sight::make('phone', 'Phone')
                        ->render(function (PhoneClick $click): string {
                            $phone = trim((string) ($click->phone ?? ''));
                            if ($phone === '') {
                                return '-';
                            }

                            return '<a href="tel:'.e($phone).'">'.e($phone).'</a>';
                        }),
                    Sight::make('source_label', 'Source')
                        ->render(fn (PhoneClick $click) => e((string) ($click->source_label ?: '-'))),
                    Sight::make('page_url', 'Page')
                        ->render(function (PhoneClick $click): string {
                            $url = trim((string) ($click->page_url ?? ''));
                            if ($url === '') {
                                return '-';
                            }

                            return '<a href="'.e($url).'" target="_blank" rel="noopener">'.e($url).'</a>';
                        }),
                    Sight::make('landing_page', 'Landing page')
                        ->render(fn (PhoneClick $click) => e((string) ($click->landing_page ?: '-'))),
                    Sight::make('referrer', 'Referrer')
                        ->render(fn (PhoneClick $click) => e((string) ($click->referrer ?: '-'))),
                    Sight::make('geo_location', 'Geo')
                        ->render(fn (PhoneClick $click) => e((string) ($click->geo_location ?: '-'))),
                    Sight::make('ip_address', 'IP')
                        ->render(fn (PhoneClick $click) => e((string) ($click->ip_address ?: '-'))),
                    Sight::make('user_agent', 'User agent')
                        ->render(fn (PhoneClick $click) => e((string) ($click->user_agent ?: '-'))),
                ]),

                Layout::legend('click', [
                    Sight::make('utm_source', 'UTM source')
                        ->render(fn (PhoneClick $click) => e((string) ($click->utm_source ?: '-'))),
                    Sight::make('utm_medium', 'UTM medium')
                        ->render(fn (PhoneClick $click) => e((string) ($click->utm_medium ?: '-'))),
                    Sight::make('utm_campaign', 'UTM campaign')
                        ->render(fn (PhoneClick $click) => e((string) ($click->utm_campaign ?: '-'))),
                    Sight::make('utm_content', 'UTM content')
                        ->render(fn (PhoneClick $click) => e((string) ($click->utm_content ?: '-'))),
                    Sight::make('utm_term', 'UTM term')
                        ->render(fn (PhoneClick $click) => e((string) ($click->utm_term ?: '-'))),
                    Sight::make('matchtype', 'Match type')
                        ->render(fn (PhoneClick $click) => e((string) ($click->matchtype ?: '-'))),
                    Sight::make('device', 'Device')
                        ->render(fn (PhoneClick $click) => e((string) ($click->device ?: '-'))),
                    Sight::make('creative', 'Creative')
                        ->render(fn (PhoneClick $click) => e((string) ($click->creative ?: '-'))),
                    Sight::make('gclid', 'GCLID')
                        ->render(fn (PhoneClick $click) => e((string) ($click->gclid ?: '-'))),
                    Sight::make('fbclid', 'FBCLID')
                        ->render(fn (PhoneClick $click) => e((string) ($click->fbclid ?: '-'))),
                    Sight::make('msclkid', 'MSCLKID')
                        ->render(fn (PhoneClick $click) => e((string) ($click->msclkid ?: '-'))),
                ]),
            ]),
        ];
    }
}
