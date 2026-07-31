<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Leads;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\LeadChange;
use App\Orchid\Layouts\Leads\LeadFiltersLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class LeadListScreen extends Screen
{
    public ?Contact $contactFilter = null;

    public function query(): iterable
    {
        $statusFilter = trim((string) request()->input('filter.status', ''));
        $contactId = (int) request()->input('filter.contact_id', 0);

        $leads = Lead::filters(LeadFiltersLayout::class)
            ->defaultSort('id', 'desc');

        // Hide spam unless the status filter explicitly selects Spam (or another status).
        if ($statusFilter === '') {
            $leads->where('status', '!=', Lead::STATUS_SPAM);
        }

        if ($contactId > 0) {
            $this->contactFilter = Contact::query()->find($contactId);
            $leads->where('contact_id', $contactId);
        }

        return [
            'leads' => $leads->paginate(50),
            'contactFilter' => $this->contactFilter,
        ];
    }

    public function name(): ?string
    {
        return $this->contactFilter
            ? 'Leads for '.$this->contactFilter->full_name
            : 'Leads';
    }

    public function description(): ?string
    {
        if ($this->contactFilter !== null) {
            return 'Showing leads linked to contact #'.$this->contactFilter->id.'.';
        }

        return 'Form submissions from the website. Spam is listed under Spam in the menu.';
    }

    public function permission(): ?iterable
    {
        return [
            'platform.leads',
        ];
    }

    public function commandBar(): iterable
    {
        if ($this->contactFilter === null) {
            return [];
        }

        return [
            Link::make('Open contact')
                ->icon('bs.person-vcard')
                ->route('platform.contacts.edit', $this->contactFilter),
            Link::make('All leads')
                ->icon('bs.arrow-left')
                ->route('platform.leads'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::view('admin.leads.assets'),
            LeadFiltersLayout::class,

            Layout::table('leads', [
                TD::make('created_at', 'Date')
                    ->sort()
                    ->render(fn (Lead $lead) => optional($lead->created_at)->format('Y-m-d H:i')),

                TD::make('status', 'Status')
                    ->sort()
                    ->cantHide()
                    ->width('200px')
                    ->render(fn (Lead $lead) => view('admin.leads.status-cell', [
                        'lead' => $lead,
                    ])),

                TD::make('full_name', 'Name')
                    ->width('190px')
                    ->render(function (Lead $lead): string {
                        $phone = trim((string) $lead->phone);
                        $name = trim((string) $lead->full_name);
                        $nameLink = '<a class="fw-semibold" href="'
                            .e(route('platform.leads.edit', $lead)).'">'
                            .e($name !== '' ? $name : 'Open lead')
                            .'</a>';

                        return $nameLink
                            .($phone !== ''
                                ? '<div class="small mt-1"><a href="tel:'
                                    .e(preg_replace('/\s+/', '', $phone) ?? $phone).'">'
                                    .e($phone)
                                    .'</a></div>'
                                : '');
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
                    ->width('165px')
                    ->render(function (Lead $lead): string {
                        $city = trim((string) ($lead->city ?? ''));

                        return e($city !== '' ? $city : '-')
                            .'<div class="small text-muted mt-1">('
                            .e($lead->trafficSourceLabel())
                            .')</div>';
                    }),

                TD::make('message', 'Message')
                    ->render(function (Lead $lead): string {
                        $message = trim((string) ($lead->message ?? ''));
                        if ($message === '') {
                            return '-';
                        }

                        return e(Str::words($message, 5, '…'));
                    }),
            ]),
        ];
    }

    public function changeStatus(Request $request): void
    {
        $validated = $request->validate([
            'lead' => ['required', 'integer', 'exists:leads,id'],
            'status' => ['required', 'string', Rule::in(array_keys(Lead::STATUSES))],
        ]);

        $user = Auth::user();
        abort_unless($user !== null, 403);

        $lead = Lead::query()->findOrFail((int) $validated['lead']);
        $from = (string) $lead->status;
        $to = $validated['status'];

        if ($from === $to) {
            Toast::info('Status unchanged.');

            return;
        }

        $lead->status = $to;
        $lead->save();

        LeadChange::recordStatusChange($lead, $from, $to, (int) $user->id);

        Toast::info('Status updated: '.$lead->statusLabel());
    }
}
