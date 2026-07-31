<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Contacts;

use App\Models\Contact;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class ContactListScreen extends Screen
{
    public function query(): iterable
    {
        return [
            'contacts' => Contact::filters()
                ->with('leads')
                ->withCount('leads')
                ->withMax('leads', 'created_at')
                ->defaultSort('id', 'desc')
                ->paginate(50),
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
        return [
            Layout::view('admin.contacts.assets'),
            Layout::table('contacts', [
                TD::make('full_name', 'Contact')
                    ->sort()
                    ->render(function (Contact $contact): string {
                        $name = trim((string) $contact->full_name);
                        $phone = trim((string) $contact->phone);

                        return '<a class="fw-semibold" href="'.e(route('platform.contacts.edit', $contact)).'">'
                            .e($name !== '' ? $name : 'Contact #'.$contact->id)
                            .'</a>'
                            .($phone !== ''
                                ? '<div class="small mt-1"><a href="tel:'.e($phone).'">'.e($phone).'</a></div>'
                                : '');
                    }),
                TD::make('email', 'Email')
                    ->render(fn (Contact $contact): string => filled($contact->email)
                        ? '<a href="mailto:'.e($contact->email).'">'.e($contact->email).'</a>'
                        : '—'),
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
