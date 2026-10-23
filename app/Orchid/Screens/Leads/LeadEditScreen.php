<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Leads;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\LeadChange;
use App\Models\LeadComment;
use App\Models\MailboxMessage;
use App\Models\User;
use App\Orchid\Screens\Concerns\QueuesCallTranscripts;
use App\Services\Ads\AppointmentSheetExporter;
use App\Services\Ads\FelixSheetExporter;
use App\Services\Ads\MicrosoftQualifiedLeadService;
use App\Services\ContactFromLeadService;
use App\Services\TrafficSourceVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Screen\Sight;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class LeadEditScreen extends Screen
{
    use QueuesCallTranscripts;

    public ?Lead $lead = null;

    public function query(Lead $lead, TrafficSourceVisibility $visibility): iterable
    {
        $visibility->authorizeOrAbort(
            Auth::user(),
            $lead,
            TrafficSourceVisibility::SECTION_LEADS
        );

        $lead->load(['comments.user', 'changes.user', 'contact', 'referralPartner', 'referralReward', 'assignee', 'tasks.assignee']);

        $this->lead = $lead;

        return [
            'lead' => $lead,
            'comments' => $lead->comments,
            'changes' => $lead->changes,
            'statusChanges' => $lead->changes->where('field', 'status')->values(),
            'contact_id' => $lead->contact_id,
            'qualification' => [
                'window_count' => $lead->qualification_window_count,
                'project_type' => $lead->qualification_project_type,
                'property_type' => $lead->qualification_property_type,
                'zip' => $lead->qualification_zip,
                'decision_maker' => $lead->qualification_decision_maker === null
                    ? null
                    : ($lead->qualification_decision_maker ? 'yes' : 'no'),
            ],
            'calls' => $lead->ringCentralCallsForPhone(),
            'tasks' => $lead->tasks,
            'mailboxMessages' => MailboxMessage::query()
                ->forParticipant($lead->email)
                ->orderByDesc('sent_at')
                ->limit(100)
                ->get(),
        ];
    }

    public function name(): ?string
    {
        return $this->lead
            ? 'Lead #'.$this->lead->id.': '.$this->lead->full_name
            : 'Lead';
    }

    public function description(): ?string
    {
        return 'Update status, leave comments, and review change history.';
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
            Link::make('Back to list')
                ->icon('bs.arrow-left')
                ->route('platform.leads'),

            Button::make('Create contact from lead')
                ->icon('bs.person-plus')
                ->method('createContact')
                ->confirm('Create a contact and link all matching non-spam leads?')
                ->canSee($this->lead?->contact_id === null),

            Button::make('Link selected contact')
                ->icon('bs.link-45deg')
                ->method('linkContact'),

            Button::make('Assign to me')
                ->icon('bs.person-check')
                ->method('assignToMe'),

            Link::make('Add task')
                ->icon('bs.plus-lg')
                ->route('platform.crm.tasks.create', ['subject_type' => Lead::class, 'subject_id' => $this->lead?->id]),
        ];

        if ($this->lead?->contact_id !== null) {
            $actions[] = Link::make('Open contact')
                ->icon('bs.person-vcard')
                ->route('platform.contacts.edit', $this->lead->contact_id);
        }

        return $actions;
    }

    public function layout(): iterable
    {
        $detailsLeft = Layout::blank([
            Layout::rows([
                Input::make('lead.full_name')
                    ->title('Name')
                    ->required()
                    ->maxlength(255),
                Input::make('lead.phone')
                    ->title('Phone')
                    ->type('tel')
                    ->required()
                    ->maxlength(50),
                Input::make('lead.email')
                    ->title('Email')
                    ->type('email')
                    ->required()
                    ->maxlength(255),
                Input::make('lead.city')
                    ->title('City')
                    ->maxlength(100),
                Input::make('lead.page_url')
                    ->title('Page')
                    ->maxlength(1000),
                TextArea::make('lead.message')
                    ->title('Message')
                    ->rows(6)
                    ->maxlength(3000),
                Select::make('lead.status')
                    ->title('Status')
                    ->options(Lead::STATUSES)
                    ->required(),
                Select::make('lead.assigned_to')
                    ->title('Assignee')
                    ->fromModel(User::class, 'name')
                    ->empty('Unassigned'),
                Select::make('contact_id')
                    ->title('Link to existing contact')
                    ->fromModel(Contact::class, 'full_name')
                    ->empty('Select contact'),
            ])->title('Lead details'),

            Layout::rows([
                Select::make('qualification.window_count')
                    ->title('Confirmed windows')
                    ->options(Lead::QUALIFICATION_WINDOW_COUNTS)
                    ->empty('Not set')
                    ->help($this->visitorCountHint()),
                Select::make('qualification.project_type')
                    ->title('Project type')
                    ->options(Lead::QUALIFICATION_PROJECT_TYPES)
                    ->empty('Not set'),
                Select::make('qualification.property_type')
                    ->title('Property type')
                    ->options(Lead::QUALIFICATION_PROPERTY_TYPES)
                    ->empty('Not set'),
                Input::make('qualification.zip')
                    ->title('Property ZIP')
                    ->maxlength(10),
                Select::make('qualification.decision_maker')
                    ->title('Owner / decision maker')
                    ->options(['yes' => 'Yes', 'no' => 'No'])
                    ->empty('Not set'),
            ])->title('Project qualification'),

            Layout::legend('lead', [
                Sight::make('id', 'ID'),
                Sight::make('created_at', 'Received')
                    ->render(fn (Lead $lead) => optional($lead->created_at)->format('Y-m-d H:i')),
                Sight::make('opening_count', 'Quantity')
                    ->render(fn (Lead $lead) => e($lead->openingSummary() !== '' ? $lead->openingSummary() : '—')),
                Sight::make('status', 'Current status')
                    ->render(fn (Lead $lead) => '<span class="lead-status-badge lead-status-badge--'.e($lead->statusColor()).'">'.e($lead->statusLabel()).'</span>'),
                Sight::make('contact', 'Contact')
                    ->render(fn (Lead $lead): string => $lead->contact
                        ? '<a href="'.e(route('platform.contacts.edit', $lead->contact)).'">'
                            .e($lead->contact->full_name).' (#'.e((string) $lead->contact->id).')</a>'
                        : '<span class="text-muted">Not linked</span>'),
                Sight::make('traffic_source', 'Last traffic source')
                    ->render(function (Lead $lead): string {
                        $detail = $lead->trafficSourceDetail();

                        return '<span class="badge bg-'.e($lead->trafficSourceColor()).' text-white">'
                            .e($lead->trafficSourceLabel())
                            .'</span>'
                            .($detail !== '' ? ' <span class="text-muted">'.e($detail).'</span>' : '');
                    }),
                Sight::make('first_traffic_source', 'First traffic source')
                    ->render(function (Lead $lead): string {
                        $detail = $lead->firstTrafficSourceDetail();

                        return '<span class="badge bg-'.e($lead->firstTrafficSourceColor()).' text-white">'
                            .e($lead->firstTrafficSourceLabel())
                            .'</span>'
                            .($detail !== '' ? ' <span class="text-muted">'.e($detail).'</span>' : '');
                    }),
                Sight::make('spam_reason', 'Spam reason')
                    ->render(fn (Lead $lead) => e($lead->metaValue('spam_reason', '-'))),
                Sight::make('ip_address', 'IP')
                    ->render(fn (Lead $lead) => e((string) ($lead->ip_address ?? '-'))),
                Sight::make('form_id', 'Form ID')
                    ->render(fn (Lead $lead) => e($lead->metaValue('form_id', '-'))),
                Sight::make('landing_page', 'Landing page')
                    ->render(fn (Lead $lead) => e($lead->metaValue('landing_page', '-'))),
                Sight::make('referrer', 'Referrer')
                    ->render(fn (Lead $lead) => e($lead->metaValue('referrer', '-'))),
                Sight::make('geo_location', 'Geo')
                    ->render(fn (Lead $lead) => e($lead->metaValue('geo_location', '-'))),
                Sight::make('screen', 'Screen')
                    ->render(fn (Lead $lead) => $lead->screen_width
                        ? e($lead->screen_width.'×'.$lead->screen_height)
                        : '—'),
                Sight::make('utm_source', 'UTM source')
                    ->render(fn (Lead $lead) => e(trim((string) ($lead->utm_source ?? '')) !== '' ? (string) $lead->utm_source : '-')),
                Sight::make('utm_medium', 'UTM medium')
                    ->render(fn (Lead $lead) => e(trim((string) ($lead->utm_medium ?? '')) !== '' ? (string) $lead->utm_medium : '-')),
                Sight::make('utm_campaign', 'UTM campaign')
                    ->render(fn (Lead $lead) => e(trim((string) ($lead->utm_campaign ?? '')) !== '' ? (string) $lead->utm_campaign : '-')),
                Sight::make('referral_partner', 'Referral partner')
                    ->render(function (Lead $lead): string {
                        $partner = $lead->referralPartner;
                        if ($partner === null) {
                            return '<span class="text-muted">—</span>';
                        }

                        $reward = $lead->referralReward;
                        $rewardLabel = $reward
                            ? ' · reward: '.e(\App\Models\ReferralReward::STATUSES[$reward->status] ?? $reward->status)
                            : '';

                        $creditCents = (int) $lead->metaValue('referral_friend_credit_cents', '0');
                        $creditLabel = $creditCents > 0
                            ? '<br><strong class="text-success">Apply referral credit: $'
                                .number_format($creditCents / 100).' off the invoice</strong>'
                            : '';

                        return '<span class="badge bg-primary text-white">'
                            .e($partner->name).' ('.e($partner->code).')</span>'
                            .$rewardLabel
                            .$creditLabel;
                    }),
                Sight::make('utm_content', 'UTM content')
                    ->render(fn (Lead $lead) => e($lead->metaValue('utm_content', '-'))),
                Sight::make('utm_term', 'UTM term')
                    ->render(fn (Lead $lead) => e($lead->metaValue('utm_term', '-'))),
                Sight::make('utm_city', 'UTM city')
                    ->render(function (Lead $lead) {
                        $regions = app(\App\Services\ServiceAreaRegions::class);

                        return e($regions->utmCityLabel(
                            $lead->metaValue('utm_city', ''),
                            $regions->platformFromAttribution([
                                'utm_source' => $lead->utm_source,
                                'gclid' => $lead->metaValue('gclid'),
                                'gbraid' => $lead->metaValue('gbraid'),
                                'wbraid' => $lead->metaValue('wbraid'),
                                'msclkid' => $lead->metaValue('msclkid'),
                            ])
                        ));
                    }),
                Sight::make('utm_redirect', 'UTM redirect')
                    ->render(fn (Lead $lead) => e($lead->metaValue('utm_redirect', '-'))),
                Sight::make('matchtype', 'Match type')
                    ->render(fn (Lead $lead) => e($lead->metaValue('matchtype', '-'))),
                Sight::make('device', 'Device')
                    ->render(fn (Lead $lead) => e($lead->metaValue('device', '-'))),
                Sight::make('creative', 'Creative')
                    ->render(fn (Lead $lead) => e($lead->metaValue('creative', '-'))),
                Sight::make('gclid', 'GCLID')
                    ->render(fn (Lead $lead) => e($lead->metaValue('gclid', '-'))),
                Sight::make('fbclid', 'FBCLID')
                    ->render(fn (Lead $lead) => e($lead->metaValue('fbclid', '-'))),
                Sight::make('msclkid', 'MSCLKID')
                    ->render(fn (Lead $lead) => e($lead->metaValue('msclkid', '-'))),
                Sight::make('bing_qualified', 'Bing Qualified')
                    ->render(fn (Lead $lead): string => $this->bingQualifiedSummary($lead)),
            ]),
        ]);

        $commentsRight = Layout::blank([
            Layout::rows([
                TextArea::make('comment')
                    ->title('New comment')
                    ->rows(4)
                    ->placeholder('Write a note for your team…'),
            ])->title('Comments'),
            Layout::view('admin.partials.comment-actions'),
            Layout::view('admin.leads.comments'),
        ]);

        return [
            Layout::view('admin.leads.assets'),

            Layout::tabs([
                'Details' => Layout::columns([
                    $detailsLeft,
                    $commentsRight,
                ]),

                'Calls' => Layout::view('admin.leads.calls'),

                'Emails' => Layout::view('admin.mailbox.client-messages'),

                'Tasks' => Layout::view('admin.crm.tasks-table'),

                'Status history' => Layout::view('admin.leads.status-history'),

                'History' => Layout::view('admin.leads.history'),
            ]),

            Layout::view('admin.partials.sticky-save', [
                'label' => 'Save changes',
                'method' => 'saveLead',
            ]),
        ];
    }

    public function saveLead(Lead $lead, Request $request)
    {
        $validated = $request->validate([
            'lead.full_name' => ['required', 'string', 'max:255'],
            'lead.phone' => ['required', 'string', 'max:50'],
            'lead.email' => ['required', 'email', 'max:255'],
            'lead.city' => ['nullable', 'string', 'max:100'],
            'lead.page_url' => ['nullable', 'string', 'max:1000'],
            'lead.message' => ['nullable', 'string', 'max:3000'],
            'lead.status' => ['required', 'string', Rule::in(array_keys(Lead::STATUSES))],
            'lead.assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'qualification.window_count' => ['nullable', Rule::in(array_keys(Lead::QUALIFICATION_WINDOW_COUNTS))],
            'qualification.project_type' => ['nullable', Rule::in(array_keys(Lead::QUALIFICATION_PROJECT_TYPES))],
            'qualification.property_type' => ['nullable', Rule::in(array_keys(Lead::QUALIFICATION_PROPERTY_TYPES))],
            'qualification.zip' => ['nullable', 'string', 'max:10'],
            'qualification.decision_maker' => ['nullable', Rule::in(['yes', 'no'])],
        ]);

        $user = Auth::user();
        abort_unless($user !== null, 403);

        $qualification = $validated['qualification'] ?? [];
        $newValues = [
            'full_name' => trim($validated['lead']['full_name']),
            'phone' => trim($validated['lead']['phone']),
            'email' => trim($validated['lead']['email']),
            'city' => trim((string) ($validated['lead']['city'] ?? '')),
            'page_url' => trim((string) ($validated['lead']['page_url'] ?? '')),
            'message' => trim((string) ($validated['lead']['message'] ?? '')),
            'status' => $validated['lead']['status'],
            'assigned_to' => $validated['lead']['assigned_to'] ?? null,
            'qualification_window_count' => (string) ($qualification['window_count'] ?? ''),
            'qualification_project_type' => (string) ($qualification['project_type'] ?? ''),
            'qualification_property_type' => (string) ($qualification['property_type'] ?? ''),
            'qualification_zip' => trim((string) ($qualification['zip'] ?? '')),
            'qualification_decision_maker' => (string) ($qualification['decision_maker'] ?? ''),
        ];
        $labels = [
            'full_name' => 'Name',
            'phone' => 'Phone',
            'email' => 'Email',
            'city' => 'City',
            'page_url' => 'Page',
            'message' => 'Message',
            'assigned_to' => 'Assignee',
            'qualification_window_count' => 'Confirmed windows',
            'qualification_project_type' => 'Project type',
            'qualification_property_type' => 'Property type',
            'qualification_zip' => 'Property ZIP',
            'qualification_decision_maker' => 'Owner / decision maker',
        ];
        $changes = [];

        foreach ($newValues as $field => $newValue) {
            $oldValue = match ($field) {
                'assigned_to' => $lead->assigned_to !== null ? (string) $lead->assigned_to : '',
                'qualification_decision_maker' => $lead->qualification_decision_maker === null
                    ? ''
                    : ($lead->qualification_decision_maker ? 'yes' : 'no'),
                default => trim((string) ($lead->{$field} ?? '')),
            };
            $compareNew = $newValue === null ? '' : (string) $newValue;
            if ($oldValue !== $compareNew) {
                $changes[$field] = [$oldValue, $compareNew];
            }
        }

        if ($changes === []) {
            Toast::info('No changes to save.');

            return redirect()->route('platform.leads.edit', $lead);
        }

        DB::transaction(function () use ($lead, $newValues, $changes, $labels, $user): void {
            foreach ($newValues as $field => $value) {
                if ($field === 'assigned_to') {
                    $lead->assigned_to = $value ?: null;
                    $lead->assigned_at = $value ? now() : null;

                    continue;
                }

                if ($field === 'qualification_decision_maker') {
                    $lead->qualification_decision_maker = $value === '' ? null : $value === 'yes';

                    continue;
                }

                $lead->{$field} = $value !== '' || in_array($field, ['full_name', 'phone', 'email', 'status'], true)
                    ? $value
                    : null;
            }
            $lead->save();

            foreach ($changes as $field => [$oldValue, $newValue]) {
                if ($field === 'status') {
                    LeadChange::recordStatusChange($lead, $oldValue, $newValue, (int) $user->id);

                    continue;
                }

                LeadChange::record(
                    $lead,
                    $field,
                    $oldValue !== '' ? $oldValue : null,
                    $newValue !== '' ? $newValue : null,
                    ($labels[$field] ?? Str::headline($field)).' updated',
                    (int) $user->id,
                );
            }
        });

        app(\App\Services\ReferralRewardService::class)->syncEligibleForLead($lead->refresh());
        if (isset($changes['status'])) {
            app(\App\Services\CrmTaskAutomation::class)->onLeadStatusChanged(
                $lead,
                $changes['status'][0],
                $changes['status'][1]
            );
            $sheetError = $this->exportStatusSheets(
                $lead,
                (string) $changes['status'][0],
                (string) $changes['status'][1]
            );
            if ($sheetError !== null) {
                Toast::warning('Lead saved. Sheet export failed: '.$sheetError);
            }
        }

        app(MicrosoftQualifiedLeadService::class)->evaluate($lead);

        Toast::info(count($changes) === 1 ? 'Lead updated.' : count($changes).' lead fields updated.');

        return redirect()->route('platform.leads.edit', $lead);
    }

    public function addComment(Lead $lead, Request $request)
    {
        $validated = $request->validate([
            'comment' => ['required', 'string', 'min:1', 'max:5000'],
            'lead.status' => ['nullable', 'string', Rule::in(array_keys(Lead::STATUSES))],
        ]);

        $user = Auth::user();
        abort_unless($user !== null, 403);

        if (! empty($validated['lead']['status'] ?? null)) {
            $from = (string) $lead->status;
            $to = $validated['lead']['status'];
            if ($from !== $to) {
                $lead->status = $to;
                $lead->save();
                LeadChange::recordStatusChange($lead, $from, $to, (int) $user->id);
                app(\App\Services\ReferralRewardService::class)->syncEligibleForLead($lead->refresh());
                app(\App\Services\CrmTaskAutomation::class)->onLeadStatusChanged($lead, $from, $to);
                $sheetError = $this->exportStatusSheets($lead, $from, $to);
                if ($sheetError !== null) {
                    Toast::warning('Status saved. Sheet export failed: '.$sheetError);
                }
                app(MicrosoftQualifiedLeadService::class)->evaluate($lead);
            }
        }

        $body = trim($validated['comment']);

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

        Toast::info('Comment added.');

        return redirect()->route('platform.leads.edit', $lead);
    }

    public function createContact(Lead $lead, ContactFromLeadService $service)
    {
        $user = Auth::user();
        abort_unless($user !== null, 403);

        $contact = $service->createOrAttach($lead, (int) $user->id);
        Toast::info('Contact ready. Matching leads were linked.');

        return redirect()->route('platform.contacts.edit', $contact);
    }

    public function linkContact(Lead $lead, Request $request, ContactFromLeadService $service)
    {
        $validated = $request->validate([
            'contact_id' => ['required', 'integer', 'exists:contacts,id'],
        ]);
        $user = Auth::user();
        abort_unless($user !== null, 403);

        $contact = Contact::query()->findOrFail((int) $validated['contact_id']);
        $service->attachToContact($lead, $contact, (int) $user->id);
        Toast::info('Lead linked to '.$contact->full_name.'.');

        return redirect()->route('platform.leads.edit', $lead);
    }

    public function assignToMe(Lead $lead)
    {
        $user = Auth::user();
        abort_unless($user !== null, 403);

        $lead->forceFill([
            'assigned_to' => $user->id,
            'assigned_at' => now(),
        ])->save();

        LeadChange::record(
            $lead,
            'assigned_to',
            null,
            (string) $user->id,
            'Assigned to '.$user->name,
            (int) $user->id,
        );

        Toast::info('Lead assigned to you.');

        return redirect()->route('platform.leads.edit', $lead);
    }

    private function visitorCountHint(): string
    {
        $answer = $this->lead?->openingSummary() ?? '';

        return ($answer !== '' ? 'Visitor answered: '.$answer.'. ' : '')
            .'Bing "Qualified Lead - 3+ Windows" is sent automatically when the visitor picks 3+ on the form; these fields are for the team.';
    }

    private function bingQualifiedSummary(Lead $lead): string
    {
        $time = fn ($value): string => $value ? e($value->format('Y-m-d H:i')) : '';

        if ($lead->bing_qualified_at === null) {
            $reason = app(MicrosoftQualifiedLeadService::class)->ineligibleReason($lead);

            return '<span class="text-muted">Not qualified'.($reason !== null ? ': '.e($reason) : '').'</span>';
        }

        $status = (string) $lead->bing_qualified_status;
        $color = match ($status) {
            Lead::BING_QUALIFIED_SENT => 'success',
            Lead::BING_QUALIFIED_FAILED => 'danger',
            Lead::BING_QUALIFIED_PENDING => 'warning',
            default => 'secondary',
        };

        $parts = ['qualified '.$time($lead->bing_qualified_at)];
        if ($lead->bing_qualified_sent_at !== null) {
            $parts[] = 'sent '.$time($lead->bing_qualified_sent_at);
        }
        if ((int) $lead->bing_qualified_attempts > 0) {
            $parts[] = 'attempts '.(int) $lead->bing_qualified_attempts;
        }

        $error = trim((string) $lead->bing_qualified_error);

        return '<span class="badge bg-'.$color.' text-white">'.e(ucfirst($status)).'</span> '
            .'<span class="text-muted">'.implode(' · ', $parts).'</span>'
            .($error !== '' ? '<br><small class="text-'.($status === Lead::BING_QUALIFIED_FAILED ? 'danger' : 'muted').'">'.e($error).'</small>' : '');
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
