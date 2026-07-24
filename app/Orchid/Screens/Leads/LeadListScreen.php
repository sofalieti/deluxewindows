<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Leads;

use App\Models\Lead;
use App\Orchid\Layouts\Leads\LeadFiltersLayout;
use Illuminate\Support\Str;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class LeadListScreen extends Screen
{
    public function query(): iterable
    {
        $statusFilter = trim((string) request()->input('filter.status', ''));

        $leads = Lead::filters(LeadFiltersLayout::class)
            ->defaultSort('id', 'desc');

        // Hide spam unless the status filter explicitly selects Spam (or another status).
        if ($statusFilter === '') {
            $leads->where('status', '!=', Lead::STATUS_SPAM);
        }

        return [
            'leads' => $leads->paginate(50),
        ];
    }

    public function name(): ?string
    {
        return 'Leads';
    }

    public function description(): ?string
    {
        return 'Form submissions from the website. Spam is hidden by default — choose Status → Spam to review it.';
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
            LeadFiltersLayout::class,

            Layout::table('leads', [
                TD::make('id', 'ID')
                    ->sort()
                    ->render(fn (Lead $lead) => Link::make((string) $lead->id)
                        ->route('platform.leads.edit', $lead)),

                TD::make('created_at', 'Date')
                    ->sort()
                    ->render(fn (Lead $lead) => optional($lead->created_at)->format('Y-m-d H:i')),

                TD::make('status', 'Status')
                    ->sort()
                    ->render(fn (Lead $lead) => e($lead->statusLabel())),

                TD::make('full_name', 'Name')
                    ->render(fn (Lead $lead) => Link::make($lead->full_name)
                        ->route('platform.leads.edit', $lead)),

                TD::make('phone', 'Phone')
                    ->render(function (Lead $lead): string {
                        $phone = trim((string) $lead->phone);
                        if ($phone === '') {
                            return '-';
                        }

                        return '<a href="tel:'.e(preg_replace('/\s+/', '', $phone) ?? $phone).'">'.e($phone).'</a>';
                    }),

                TD::make('email', 'Email')
                    ->render(function (Lead $lead): string {
                        $email = trim((string) $lead->email);
                        if ($email === '') {
                            return '-';
                        }

                        return '<a href="mailto:'.e($email).'">'.e($email).'</a>';
                    }),

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

                        $label = Str::limit($url, 60);

                        return '<a href="'.e($url).'" target="_blank" rel="noopener">'.e($label).'</a>';
                    }),

                TD::make('message', 'Message')
                    ->render(fn (Lead $lead) => e(Str::limit((string) ($lead->message ?? ''), 80, '...'))),
            ]),
        ];
    }
}
