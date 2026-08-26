<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Contacts;

use App\Models\Contact;
use App\Services\RingCentralPhoneCallStatsService;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class ContactListScreen extends Screen
{
    /** @var array<string, array{inbound: int, outbound: int, connected: bool, connected_count: int}> */
    private array $callStatsByPhone = [];

    public function query(): iterable
    {
        $contacts = Contact::filters()
            ->with(['leads', 'emailAddresses'])
            ->withCount('leads')
            ->withMax('leads', 'created_at')
            ->defaultSort('id', 'desc')
            ->paginate(50);

        $this->callStatsByPhone = app(RingCentralPhoneCallStatsService::class)->statsForPhones(
            collect($contacts->items())->pluck('phone')->all()
        );

        return [
            'contacts' => $contacts,
        ];
    }

    public function name(): ?string
    {
        return 'Contacts';
    }

    public function description(): ?string
    {
        return 'Clients and every website lead linked to them.';
    }

    public function permission(): ?iterable
    {
        return ['platform.contacts'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make('Add contact')
                ->icon('bs.person-plus')
                ->route('platform.contacts.create'),
        ];
    }

    public function layout(): iterable
    {
        $callStats = app(RingCentralPhoneCallStatsService::class);

        return [
            Layout::view('admin.contacts.assets'),
            Layout::table('contacts', [
                TD::make('full_name', 'Contact')
                    ->sort()
                    ->render(function (Contact $contact): string {
                        $name = trim((string) $contact->full_name);
                        $phone = trim((string) $contact->phone);
                        $emails = $contact->allNormalizedEmails();

                        $html = '<a class="fw-semibold" href="'.e(route('platform.contacts.edit', $contact)).'">'
                            .e($name !== '' ? $name : 'Contact #'.$contact->id)
                            .'</a>';

                        if ($phone !== '') {
                            $html .= '<div class="small mt-1"><a href="tel:'.e($phone).'">'.e($phone).'</a></div>';
                        }
                        foreach ($emails as $email) {
                            $html .= '<div class="small mt-1"><a href="mailto:'.e($email).'">'.e($email).'</a></div>';
                        }

                        return $html;
                    }),
                TD::make('calls', 'Calls')
                    ->width('110px')
                    ->render(fn (Contact $contact) => view('admin.partials.call-stats-cell', [
                        'stats' => $callStats->lookup($this->callStatsByPhone, $contact->phone),
                    ])),
                TD::make('city', 'City')
                    ->render(fn (Contact $contact): string => e((string) ($contact->city ?: '—'))),
                TD::make('leads_count', 'Leads')
                    ->align(TD::ALIGN_CENTER)
                    ->render(function (Contact $contact): string {
                        $count = (int) $contact->leads_count;
                        $url = route('platform.leads', [
                            'filter' => ['contact_id' => $contact->id],
                        ]);

                        return '<a class="badge bg-primary text-white text-decoration-none" href="'
                            .e($url).'" title="Open leads for this contact">'
                            .e((string) $count)
                            .'</a>';
                    }),
                TD::make('traffic', 'Traffic summary')
                    ->render(function (Contact $contact): string {
                        $parts = collect($contact->trafficSummary())
                            ->map(fn (array $item): string => e((string) $item['count']).' '.e($item['label']))
                            ->all();

                        return $parts !== [] ? implode(' · ', $parts) : '—';
                    }),
                TD::make('leads_max_created_at', 'Last lead')
                    ->render(fn (Contact $contact): string => $contact->leads_max_created_at
                        ? e((string) $contact->leads_max_created_at)
                        : '—'),
                TD::make('created_at', 'Created')
                    ->sort()
                    ->render(fn (Contact $contact): string => e(optional($contact->created_at)->format('Y-m-d H:i') ?? '—')),
            ]),
        ];
    }
}
