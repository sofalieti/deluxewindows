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
        $contactId = (int) request()->input('filter.contact_id', 0);

        if ($contactId > 0) {
            $this->contactFilter = Contact::query()->find($contactId);
        }

        $leads = Lead::filters(LeadFiltersLayout::class)
            ->defaultSort('id', 'desc')
            ->where('status', '!=', Lead::STATUS_SPAM);

        $spamLeads = Lead::query()
            ->where('status', Lead::STATUS_SPAM)
            ->defaultSort('id', 'desc');

        if ($contactId > 0) {
            $leads->where('contact_id', $contactId);
            $spamLeads->where('contact_id', $contactId);
        }

        return [
            'leads' => $leads->paginate(50, pageName: 'page'),
            'spamLeads' => $spamLeads->paginate(50, pageName: 'spam_page'),
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

        return 'Form submissions from the website. Spam is under the Spam tab.';
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

            Layout::tabs([
                'Leads' => Layout::table('leads', $this->leadColumns(spamTab: false)),
                'Spam' => Layout::table('spamLeads', $this->leadColumns(spamTab: true)),
            ]),
        ];
    }

    /**
     * @return list<TD>
     */
    private function leadColumns(bool $spamTab): array
    {
        $columns = [
            TD::make('created_at', 'Date')
                ->sort()
                ->render(fn (Lead $lead) => optional($lead->created_at)->format('Y-m-d H:i')),
        ];

        if ($spamTab) {
            $columns[] = TD::make('spam_reason', 'Reason')
                ->render(fn (Lead $lead) => e($lead->metaValue('spam_reason', '-')));
        }

        $columns[] = TD::make('status', 'Status')
            ->sort()
            ->cantHide()
            ->width('200px')
            ->render(fn (Lead $lead) => view('admin.leads.status-cell', [
                'lead' => $lead,
            ]));

        $columns[] = TD::make('full_name', 'Name')
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
            });

        $columns[] = TD::make('email', 'Email')
            ->render(function (Lead $lead): string {
                $email = trim((string) $lead->email);
                if ($email === '') {
                    return '-';
                }

                return '<a href="mailto:'.e($email).'">'.e($email).'</a>';
            });

        $columns[] = TD::make('city', 'City')
            ->width('165px')
            ->render(function (Lead $lead) use ($spamTab): string {
                $city = trim((string) ($lead->city ?? ''));

                if ($spamTab) {
                    return e($city !== '' ? $city : '-');
                }

                return e($city !== '' ? $city : '-')
                    .'<div class="small text-muted mt-1">('
                    .e($lead->trafficSourceLabel())
                    .')</div>';
            });

        $columns[] = TD::make('message', 'Message')
            ->render(function (Lead $lead): string {
                $message = trim((string) ($lead->message ?? ''));
                if ($message === '') {
                    return '-';
                }

                return e(Str::words($message, 5, '…'));
            });

        return $columns;
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
