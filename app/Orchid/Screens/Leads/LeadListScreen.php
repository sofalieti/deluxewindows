<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Leads;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\LeadChange;
use App\Orchid\Layouts\Leads\LeadFiltersLayout;
use App\Services\RingCentralPhoneCallStatsService;
use App\Services\TrafficSourceVisibility;
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

    /** @var array<string, array{inbound: int, outbound: int, connected: bool, connected_count: int}> */
    private array $callStatsByPhone = [];

    public function query(): iterable
    {
        $contactId = (int) request()->input('filter.contact_id', 0);

        if ($contactId > 0) {
            $this->contactFilter = Contact::query()->find($contactId);
        }

        $user = Auth::user();

        $leads = Lead::filters(LeadFiltersLayout::class)
            ->visibleTo($user)
            ->with('assignee')
            ->defaultSort('id', 'desc')
            ->where('status', '!=', Lead::STATUS_SPAM);

        $mineLeads = Lead::query()
            ->visibleTo($user)
            ->with('assignee')
            ->where('status', '!=', Lead::STATUS_SPAM)
            ->where('assigned_to', $user?->id)
            ->defaultSort('id', 'desc');

        $spamLeads = Lead::query()
            ->visibleTo($user)
            ->where('status', Lead::STATUS_SPAM)
            ->defaultSort('id', 'desc');

        if ($contactId > 0) {
            $leads->where('contact_id', $contactId);
            $mineLeads->where('contact_id', $contactId);
            $spamLeads->where('contact_id', $contactId);
        }

        $leadsPage = $leads->paginate(50, pageName: 'page');
        $minePage = $mineLeads->paginate(50, pageName: 'mine_page');
        $spamPage = $spamLeads->paginate(50, pageName: 'spam_page');

        $this->callStatsByPhone = app(RingCentralPhoneCallStatsService::class)->statsForPhones(
            collect($leadsPage->items())
                ->concat($minePage->items())
                ->concat($spamPage->items())
                ->pluck('phone')
                ->all()
        );

        return [
            'leads' => $leadsPage,
            'mineLeads' => $minePage,
            'spamLeads' => $spamPage,
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
        $sources = app(TrafficSourceVisibility::class)
            ->allowedBucketLabels(Auth::user(), TrafficSourceVisibility::SECTION_LEADS);
        $sourceNote = $sources === []
            ? ' No traffic sources are enabled for your role.'
            : ' Visible sources: '.implode(', ', $sources).'.';

        if ($this->contactFilter !== null) {
            return 'Showing leads linked to contact #'.$this->contactFilter->id.'.'.$sourceNote;
        }

        return 'Form submissions from the website. Spam is under the Spam tab.'.$sourceNote;
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
                'Mine' => Layout::table('mineLeads', $this->leadColumns(spamTab: false)),
                'Spam' => Layout::table('spamLeads', $this->leadColumns(spamTab: true)),
            ]),
        ];
    }

    /**
     * @return list<TD>
     */
    private function leadColumns(bool $spamTab): array
    {
        $callStats = app(RingCentralPhoneCallStatsService::class);

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

        $columns[] = TD::make('assigned_to', 'Assignee')
            ->width('120px')
            ->render(fn (Lead $lead) => e($lead->assignee?->name ?? '—'));

        $columns[] = TD::make('full_name', 'Name')
            ->width('200px')
            ->render(function (Lead $lead): string {
                $phone = trim((string) $lead->phone);
                $email = trim((string) $lead->email);
                $name = trim((string) $lead->full_name);
                $nameLink = '<a class="fw-semibold" href="'
                    .e(route('platform.leads.edit', $lead)).'">'
                    .e($name !== '' ? $name : 'Open lead')
                    .'</a>';

                $html = $nameLink;
                if ($phone !== '') {
                    $html .= '<div class="small mt-1"><a href="tel:'
                        .e(preg_replace('/\s+/', '', $phone) ?? $phone).'">'
                        .e($phone)
                        .'</a></div>';
                }
                if ($email !== '') {
                    $html .= '<div class="small mt-1"><a href="mailto:'.e($email).'">'.e($email).'</a></div>';
                }

                return $html;
            });

        $columns[] = TD::make('calls', 'Calls')
            ->width('110px')
            ->render(fn (Lead $lead) => view('admin.partials.call-stats-cell', [
                'stats' => $callStats->lookup($this->callStatsByPhone, $lead->phone),
            ]));

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
        app(\App\Services\ReferralRewardService::class)->syncEligibleForLead($lead->refresh());
        app(\App\Services\CrmTaskAutomation::class)->onLeadStatusChanged($lead, $from, $to);

        Toast::info('Status updated: '.$lead->statusLabel());
    }
}
