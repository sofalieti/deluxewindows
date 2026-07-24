<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Leads;

use App\Models\Lead;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Illuminate\Support\Str;

class LeadListScreen extends Screen
{
    public function query(): iterable
    {
        return [
            'leads' => Lead::filters()
                ->defaultSort('id', 'desc')
                ->paginate(50),
        ];
    }

    public function name(): ?string
    {
        return 'Leads';
    }

    public function description(): ?string
    {
        return 'All form submissions saved from the website.';
    }

    public function commandBar(): iterable
    {
        return [];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('leads', [
                TD::make('id', 'ID')
                    ->render(fn (Lead $lead) => e((string) $lead->id)),

                TD::make('created_at', 'Date')
                    ->render(fn (Lead $lead) => optional($lead->created_at)->format('Y-m-d H:i')),

                TD::make('full_name', 'Name')
                    ->render(fn (Lead $lead) => e($lead->full_name)),

                TD::make('phone', 'Phone')
                    ->render(fn (Lead $lead) => e($lead->phone)),

                TD::make('email', 'Email')
                    ->render(fn (Lead $lead) => e($lead->email)),

                TD::make('city', 'City')
                    ->render(fn (Lead $lead) => e((string) ($lead->city ?? '-'))),

                TD::make('utm', 'UTM')
                    ->render(function (Lead $lead): string {
                        $parts = array_filter([
                            $lead->utm_source ? 'src: '.$lead->utm_source : null,
                            $lead->utm_medium ? 'med: '.$lead->utm_medium : null,
                            $lead->utm_campaign ? 'cmp: '.$lead->utm_campaign : null,
                        ]);

                        return e($parts !== [] ? implode(' | ', $parts) : '-');
                    }),

                TD::make('page_url', 'Page')
                    ->render(function (Lead $lead): string {
                        $url = trim((string) ($lead->page_url ?? ''));
                        if ($url === '') {
                            return '-';
                        }

                        $label = Str::limit($url, 80);

                        return '<a href="'.e($url).'" target="_blank" rel="noopener">'.e($label).'</a>';
                    }),

                TD::make('message', 'Message')
                    ->render(fn (Lead $lead) => e(Str::limit((string) ($lead->message ?? ''), 120, '...'))),
            ]),
        ];
    }
}
