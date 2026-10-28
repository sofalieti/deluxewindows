<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Leads;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\LeadChange;
use App\Models\LeadComment;
use App\Models\User;
use App\Orchid\Layouts\Leads\LeadFiltersLayout;
use App\Services\Ads\AppointmentSheetExporter;
use App\Services\Ads\FelixSheetExporter;
use App\Services\Ads\LeadListSheetExporter;
use App\Services\Mailbox\MailboxEmailStatsService;
use App\Services\RingCentralPhoneCallStatsService;
use App\Services\TrafficSourceVisibility;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Orchid\Screen\Actions\Button;
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

    /** @var array<string, array{inbound: int, outbound: int, last_direction: ?string}> */
    private array $mailStatsByEmail = [];

    public function query(): iterable
    {
        $contactId = (int) request()->input('filter.contact_id', 0);

        if ($contactId > 0) {
            $this->contactFilter = Contact::query()->find($contactId);
        }

        $user = Auth::user();

        $leads = Lead::filters(LeadFiltersLayout::class)
            ->visibleTo($user)
            ->with(['assignee', 'latestComment.user'])
            ->defaultSort('id', 'desc')
            ->where('status', '!=', Lead::STATUS_SPAM);

        $mineLeads = Lead::query()
            ->visibleTo($user)
            ->with(['assignee', 'latestComment.user'])
            ->where('status', '!=', Lead::STATUS_SPAM)
            ->where('assigned_to', $user?->id)
            ->defaultSort('id', 'desc');

        $spamLeads = Lead::query()
            ->visibleTo($user)
            ->with(['assignee', 'latestComment.user'])
            ->where('status', Lead::STATUS_SPAM)
            ->defaultSort('id', 'desc');

        $unprocessedLeads = Lead::query()
            ->visibleTo($user)
            ->with(['assignee', 'latestComment.user'])
            ->unprocessed()
            ->defaultSort('id', 'desc');

        if ($contactId > 0) {
            $leads->where('contact_id', $contactId);
            $mineLeads->where('contact_id', $contactId);
            $spamLeads->where('contact_id', $contactId);
            $unprocessedLeads->where('contact_id', $contactId);
        }

        $leadsPage = $leads->paginate(50, pageName: 'page');
        $minePage = $mineLeads->paginate(50, pageName: 'mine_page');
        $spamPage = $spamLeads->paginate(50, pageName: 'spam_page');
        $unprocessedPage = $unprocessedLeads->paginate(50, pageName: 'unprocessed_page');
        $microsoftPage = $this->channelLeads(['microsoft_ads'], 'microsoft_page');
        $directPage = $this->channelLeads(['direct'], 'direct_page');
        $googlePage = $this->channelLeads(['google_ads'], 'google_page');
        $seoPage = $this->channelLeads(['seo_google', 'seo_bing', 'seo_other'], 'seo_page');
        $chatgptPage = $this->chatgptLeads();

        $pageLeads = collect($leadsPage->items())
            ->concat($minePage->items())
            ->concat($spamPage->items())
            ->concat($unprocessedPage->items())
            ->concat($microsoftPage->items())
            ->concat($directPage->items())
            ->concat($googlePage->items())
            ->concat($seoPage->items())
            ->concat($chatgptPage->items());

        $this->callStatsByPhone = app(RingCentralPhoneCallStatsService::class)->statsForPhones(
            $pageLeads->pluck('phone')->all()
        );
        $this->mailStatsByEmail = app(MailboxEmailStatsService::class)->statsForEmails(
            $pageLeads->pluck('email')->all()
        );

        return [
            'leads' => $leadsPage,
            'mineLeads' => $minePage,
            'spamLeads' => $spamPage,
            'unprocessedLeads' => $unprocessedPage,
            'microsoftLeads' => $microsoftPage,
            'directLeads' => $directPage,
            'googleLeads' => $googlePage,
            'seoLeads' => $seoPage,
            'chatgptLeads' => $chatgptPage,
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

        return 'Form submissions from the website. Untouched New leads are under Unprocessed. Spam is under the Spam tab.'.$sourceNote;
    }

    public function permission(): ?iterable
    {
        return [
            'platform.leads',
        ];
    }

    public function commandBar(): iterable
    {
        $actions = [
            Button::make('Export appointments')
                ->icon('bs.file-earmark-spreadsheet')
                ->method('exportAppointments')
                ->confirm('Append appointment leads that have a Google GCLID into the appointments spreadsheet?'),
            Button::make('Export Felix')
                ->icon('bs.file-earmark-spreadsheet')
                ->method('exportFelix')
                ->confirm('Append Transfered to Felix leads into the Felix spreadsheet?'),
            Button::make('Export last 3 days')
                ->icon('bs.file-earmark-spreadsheet')
                ->method('exportRecentLeads')
                ->confirm('Write every lead from the last 3 days into the leads spreadsheet? Rows already there are overwritten with the current status.'),
        ];

        if ($this->contactFilter !== null) {
            $actions[] = Link::make('Open contact')
                ->icon('bs.person-vcard')
                ->route('platform.contacts.edit', $this->contactFilter);
            $actions[] = Link::make('All leads')
                ->icon('bs.arrow-left')
                ->route('platform.leads');
        }

        return $actions;
    }

    public function exportAppointments(AppointmentSheetExporter $exporter): void
    {
        try {
            $result = $exporter->exportPending();
        } catch (\Throwable $e) {
            report($e);
            Toast::error(Str::limit($e->getMessage(), 300));

            return;
        }

        if ($result['count'] === 0) {
            Toast::info('No new appointment leads to export.');

            return;
        }

        Toast::success('Sent '.$result['count'].' appointment lead(s) to the sheet.');
    }

    public function exportRecentLeads(LeadListSheetExporter $exporter): void
    {
        try {
            $result = $exporter->exportCreatedWithinDays(3);
        } catch (\Throwable $e) {
            report($e);
            Toast::error(Str::limit($e->getMessage(), 300));

            return;
        }

        if ($result['count'] === 0) {
            Toast::info('No leads from the last 3 days.');

            return;
        }

        Toast::success('Wrote '.$result['count'].' lead(s) to the sheet.');
        if ($result['spreadsheet_url'] !== '') {
            Toast::info($result['spreadsheet_url']);
        }
    }

    public function exportFelix(FelixSheetExporter $exporter): void
    {
        try {
            $result = $exporter->exportPending();
        } catch (\Throwable $e) {
            report($e);
            Toast::error(Str::limit($e->getMessage(), 300));

            return;
        }

        if ($result['count'] === 0) {
            Toast::info('No new Felix leads to export.');

            return;
        }

        Toast::success('Sent '.$result['count'].' Felix lead(s) to the sheet.');
    }

    public function sendAppointment(Request $request, AppointmentSheetExporter $exporter): void
    {
        $validated = $request->validate([
            'lead' => ['required', 'integer', 'exists:leads,id'],
        ]);

        $lead = Lead::query()->findOrFail((int) $validated['lead']);

        try {
            $sent = $exporter->exportLead($lead);
        } catch (\Throwable $e) {
            report($e);
            Toast::error(Str::limit($e->getMessage(), 300));

            return;
        }

        if ($sent) {
            Toast::success('Appointment sent to the sheet.');

            return;
        }

        Toast::info($exporter->shouldExport($lead)
            ? 'This lead is already in the sheet.'
            : 'Only appointment leads with a Google GCLID are sent. Bing is skipped.');
    }

    public function sendFelix(Request $request, FelixSheetExporter $exporter): void
    {
        $validated = $request->validate([
            'lead' => ['required', 'integer', 'exists:leads,id'],
        ]);

        $lead = Lead::query()->findOrFail((int) $validated['lead']);

        try {
            $sent = $exporter->exportLead($lead);
        } catch (\Throwable $e) {
            report($e);
            Toast::error(Str::limit($e->getMessage(), 300));

            return;
        }

        Toast::info($sent ? 'Lead sent to the Felix sheet.' : 'This lead is already in the Felix sheet.');
    }

    public function layout(): iterable
    {
        return [
            Layout::view('admin.leads.assets'),
            LeadFiltersLayout::class,

            Layout::tabs([
                'Microsoft' => Layout::table('microsoftLeads', $this->leadColumns(spamTab: false)),
                'Direct' => Layout::table('directLeads', $this->leadColumns(spamTab: false)),
                'Google' => Layout::table('googleLeads', $this->leadColumns(spamTab: false)),
                'SEO' => Layout::table('seoLeads', $this->leadColumns(spamTab: false)),
                'ChatGPT' => Layout::table('chatgptLeads', $this->leadColumns(spamTab: false)),
                'Leads' => Layout::table('leads', $this->leadColumns(spamTab: false)),
                'Unprocessed' => Layout::table('unprocessedLeads', $this->leadColumns(spamTab: false)),
                'Mine' => Layout::table('mineLeads', $this->leadColumns(spamTab: false)),
                'Spam' => Layout::table('spamLeads', $this->leadColumns(spamTab: true)),
            ]),
        ];
    }

    /**
     * @param  list<string>  $sources
     */
    private function channelLeads(array $sources, string $pageName): LengthAwarePaginator
    {
        return $this->sourcedLeads()
            ->whereIn('traffic_source', $sources)
            ->paginate(50, ['*'], $pageName);
    }

    private function chatgptLeads(): LengthAwarePaginator
    {
        return $this->sourcedLeads()
            ->where(function (Builder $query): void {
                $query->where('traffic_source', 'chatgpt')
                    ->orWhere('utm_source', 'like', '%chatgpt%')
                    ->orWhere('utm_source', 'like', '%openai%')
                    ->orWhere('meta->referrer', 'like', '%chatgpt.com%')
                    ->orWhere('meta->referrer', 'like', '%openai.com%');
            })
            ->paginate(50, ['*'], 'chatgpt_page');
    }

    private function sourcedLeads(): Builder
    {
        $query = Lead::filters(LeadFiltersLayout::class)
            ->visibleTo(Auth::user())
            ->with(['assignee', 'latestComment.user'])
            ->where('status', '!=', Lead::STATUS_SPAM)
            ->defaultSort('id', 'desc');

        $contactId = (int) request()->input('filter.contact_id', 0);
        if ($contactId > 0) {
            $query->where('contact_id', $contactId);
        }

        return $query;
    }

    /**
     * @return list<TD>
     */
    private function leadColumns(bool $spamTab): array
    {
        $callStats = app(RingCentralPhoneCallStatsService::class);
        $mailStats = app(MailboxEmailStatsService::class);
        $assignees = User::query()->orderBy('name')->pluck('name', 'id');

        $columns = [
            TD::make('created_at', 'Date')
                ->sort()
                ->width('140px')
                ->render(fn (Lead $lead) => view('admin.leads.date-cell', [
                    'lead' => $lead,
                ])),
            TD::make('opening_count', 'Qty')
                ->width('150px')
                ->render(fn (Lead $lead) => view('admin.leads.qty-cell', [
                    'lead' => $lead,
                ])),
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
                'assignees' => $assignees,
            ]));

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

        $columns[] = TD::make('mail', 'Mail')
            ->width('100px')
            ->render(fn (Lead $lead) => view('admin.leads.mail-stats-cell', [
                'stats' => $mailStats->lookup($this->mailStatsByEmail, $lead->email),
            ]));

        $columns[] = TD::make('note', 'Note')
            ->width('220px')
            ->render(fn (Lead $lead) => view('admin.leads.note-cell', [
                'lead' => $lead,
            ]));

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
            return;
        }

        $lead->status = $to;
        $lead->save();

        LeadChange::recordStatusChange($lead, $from, $to, (int) $user->id);
        app(\App\Services\ReferralRewardService::class)->syncEligibleForLead($lead->refresh());
        app(\App\Services\CrmTaskAutomation::class)->onLeadStatusChanged($lead, $from, $to);

        $alreadyInSheet = $lead->appointments_sheet_exported_at !== null;
        $sheetError = $this->exportStatusSheets($lead, $from, $to);
        app(\App\Services\Ads\MicrosoftQualifiedLeadService::class)->evaluate($lead);
        $lead->refresh();
        if ($sheetError !== null) {
            Toast::warning('Status updated. Sheet export failed: '.$sheetError);
        } elseif ($to === Lead::STATUS_APPOINTMENT && ! $alreadyInSheet && $lead->appointments_sheet_exported_at !== null) {
            Toast::info('Status updated: '.$lead->statusLabel().'. Sent to the appointments sheet.');
        } elseif ($to === Lead::STATUS_TRANSFERED_TO_FELIX && $lead->felix_sheet_exported_at !== null) {
            Toast::info('Status updated: '.$lead->statusLabel().'. Sent to the Felix sheet.');
        } else {
            Toast::info('Status updated: '.$lead->statusLabel());
        }
    }

    public function changeAssignee(Request $request): void
    {
        $assignedRaw = $request->input('assigned_to');
        if ($assignedRaw === '' || $assignedRaw === null) {
            $request->merge(['assigned_to' => null]);
        }

        $validated = $request->validate([
            'lead' => ['required', 'integer', 'exists:leads,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $user = Auth::user();
        abort_unless($user !== null, 403);

        $lead = Lead::query()->findOrFail((int) $validated['lead']);
        $from = $lead->assigned_to !== null ? (string) $lead->assigned_to : '';
        $to = isset($validated['assigned_to']) && $validated['assigned_to'] !== null
            ? (string) $validated['assigned_to']
            : '';

        if ($from === $to) {
            return;
        }

        $fromName = $from !== ''
            ? (User::query()->find((int) $from)?->name ?? $from)
            : 'Unassigned';
        $toName = $to !== ''
            ? (User::query()->find((int) $to)?->name ?? $to)
            : 'Unassigned';

        $lead->assigned_to = $to !== '' ? (int) $to : null;
        $lead->assigned_at = $to !== '' ? now() : null;
        $lead->save();

        LeadChange::record(
            $lead,
            'assigned_to',
            $from !== '' ? $from : null,
            $to !== '' ? $to : null,
            'Assignee: '.$fromName.' → '.$toName,
            (int) $user->id,
        );

        Toast::info('Assignee updated: '.$toName);
    }

    public function addNote(Request $request): void
    {
        $validated = $request->validate([
            'lead' => ['required', 'integer', 'exists:leads,id'],
            'note' => ['required', 'string', 'min:1', 'max:5000'],
        ]);

        $user = Auth::user();
        abort_unless($user !== null, 403);

        $lead = Lead::query()->findOrFail((int) $validated['lead']);
        $body = trim($validated['note']);

        LeadComment::query()->create([
            'lead_id' => $lead->id,
            'user_id' => $user->id,
            'body' => $body,
        ]);

        LeadChange::record(
            $lead,
            'comment',
            null,
            Str::limit($body, 300),
            'Added a comment',
            (int) $user->id,
        );

        Toast::info('Note saved.');
    }

    private function exportStatusSheets(Lead $lead, string $from, string $to): ?string
    {
        $errors = array_values(array_filter([
            app(AppointmentSheetExporter::class)->onStatusChanged($lead, $from, $to),
            app(FelixSheetExporter::class)->onStatusChanged($lead, $from, $to),
        ]));

        return $errors === [] ? null : implode(' ', $errors);
    }
}
