<?php

declare(strict_types=1);

namespace App\Orchid\Screens\PhoneClicks;

use App\Models\PhoneClick;
use Illuminate\Support\Str;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class PhoneClickListScreen extends Screen
{
    public function query(): iterable
    {
        return [
            'clicks' => PhoneClick::query()
                ->defaultSort('id', 'desc')
                ->paginate(50),
        ];
    }

    public function name(): ?string
    {
        return 'Phone clicks';
    }

    public function description(): ?string
    {
        return 'Click-to-call events from tel: links on the website, with UTM and landing data.';
    }

    public function permission(): ?iterable
    {
        return [
            'platform.leads',
        ];
    }

    public function commandBar(): iterable
    {
        return [];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('clicks', [
                TD::make('created_at', 'Date')
                    ->sort()
                    ->render(fn (PhoneClick $click) => optional($click->created_at)->format('Y-m-d H:i')),

                TD::make('phone', 'Phone')
                    ->render(function (PhoneClick $click): string {
                        $phone = trim((string) ($click->phone ?? ''));
                        if ($phone === '') {
                            return '-';
                        }

                        return '<a href="tel:'.e($phone).'">'.e($phone).'</a>';
                    }),

                TD::make('source_label', 'Source')
                    ->render(fn (PhoneClick $click) => e((string) ($click->source_label ?: '-'))),

                TD::make('utm', 'UTM')
                    ->width('280px')
                    ->render(function (PhoneClick $click): string {
                        $parts = $click->utmSummaryParts();

                        return e($parts !== [] ? implode(' | ', $parts) : '-');
                    }),

                TD::make('landing_page', 'Landing')
                    ->render(fn (PhoneClick $click) => e(Str::limit((string) ($click->landing_page ?: '-'), 40))),

                TD::make('page_url', 'Page')
                    ->render(function (PhoneClick $click): string {
                        $url = trim((string) ($click->page_url ?? ''));
                        if ($url === '') {
                            return '-';
                        }

                        return '<a href="'.e($url).'" target="_blank" rel="noopener">'.e(Str::limit($url, 50)).'</a>';
                    }),

                TD::make('id', 'Details')
                    ->render(fn (PhoneClick $click) => Link::make('Open')
                        ->icon('bs.eye')
                        ->route('platform.phone-clicks.view', $click)),
            ]),
        ];
    }
}
