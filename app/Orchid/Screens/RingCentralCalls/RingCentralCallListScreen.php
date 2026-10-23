<?php

declare(strict_types=1);

namespace App\Orchid\Screens\RingCentralCalls;

use App\Models\Contact;
use App\Models\CrmNote;
use App\Models\Lead;
use App\Models\RingCentralCall;
use App\Models\RingCentralCallSyncState;
use App\Models\RingCentralExcludedNumber;
use App\Models\RingCentralStaffNumber;
use App\Orchid\Screens\Concerns\QueuesCallTranscripts;
use App\Services\PromotionControlService;
use App\Services\RingCentralCallLogService;
use App\Services\RingCentralCallSyncService;
use App\Services\RingCentralPhoneCallStatsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Throwable;

class RingCentralCallListScreen extends Screen
{
    use QueuesCallTranscripts;

    /** @var list<string> */
    private array $monitoredPhones = [];

    private string $lastSyncedLabel = 'never';

    public function query(
        PromotionControlService $promotions,
        RingCentralCallLogService $callLog
    ): iterable {
        $this->monitoredPhones = array_values(array_filter(
            array_map(
                fn (string $phone): string => $callLog->normalizePhone($phone),
                $promotions->ringCentralPhones(),
            ),
            fn (string $phone): bool => $phone !== '',
        ));
        $this->lastSyncedLabel = $this->resolveLastSyncedLabel();

        if (! Schema::hasTable('ringcentral_calls')
            || ! Schema::hasTable('ringcentral_excluded_numbers')
            || ! Schema::hasTable('ringcentral_staff_numbers')) {
            Toast::error('RingCentral call tables are missing. Run: php artisan migrate');

            return [
                'inboundCalls' => new LengthAwarePaginator([], 0, 50, 1, ['pageName' => 'inbound_page']),
                'outboundCalls' => new LengthAwarePaginator([], 0, 50, 1, ['pageName' => 'outbound_page']),
                'otherCalls' => new LengthAwarePaginator([], 0, 50, 1, ['pageName' => 'other_page']),
                'staffNumbers' => collect(),
                'excludedNumbers' => collect(),
            ];
        }

        $inbound = $this->presentCalls(RingCentralCall::query()
            ->visible()
            ->notStaff()
            ->inbound()
            ->onMonitoredLines($this->monitoredPhones)
            ->with(['contact', 'latestNote.user'])
            ->defaultSort('started_at', 'desc')
            ->paginate(50, pageName: 'inbound_page'));
        $outbound = $this->presentCalls(RingCentralCall::query()
            ->visible()
            ->notStaff()
            ->outbound()
            ->onMonitoredLines($this->monitoredPhones)
            ->with(['contact', 'latestNote.user'])
            ->defaultSort('started_at', 'desc')
            ->paginate(50, pageName: 'outbound_page'));
        $other = $this->presentCalls(RingCentralCall::query()
            ->visible()
            ->notStaff()
            ->onOtherLines($this->monitoredPhones)
            ->with(['contact', 'latestNote.user'])
            ->defaultSort('started_at', 'desc')
            ->paginate(50, pageName: 'other_page'));

        $staff = RingCentralStaffNumber::query()
            ->whereNull('restored_at')
            ->with('markedBy')
            ->orderBy('name')
            ->get();
        $this->presentStaff($staff);

        return [
            'inboundCalls' => $inbound,
            'outboundCalls' => $outbound,
            'otherCalls' => $other,
            'staffNumbers' => $staff,
            'excludedNumbers' => RingCentralExcludedNumber::query()
                ->whereNull('restored_at')
                ->withCount(['calls as hidden_calls_count'])
                ->with('excludedBy')
                ->orderByDesc('excluded_at')
                ->get(),
        ];
    }

    public function name(): ?string
    {
        return 'RingCentral calls';
    }

    public function description(): ?string
    {
        $phones = $this->monitoredPhones !== []
            ? implode(', ', $this->monitoredPhones)
            : 'configured numbers';

        return 'Inbound calls on the main numbers are listed first. Outbound calls and staff numbers are on their own tabs. Main numbers: '.$phones
            .'. Auto-sync hourly; last sync: '.$this->lastSyncedLabel.'.';
    }

    public function permission(): ?iterable
    {
        return ['platform.leads'];
    }

    public function commandBar(): iterable
    {
        return [
            Button::make('Sync now')
                ->icon('bs.arrow-repeat')
                ->method('syncNow')
                ->confirm('Pull all account voice calls from RingCentral now (main numbers, other lines, and contact linking)?'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::view('admin.ringcentral-calls.assets'),
            Layout::tabs([
                'Inbound' => Layout::table('inboundCalls', $this->callColumns(showExclude: true)),
                'Outbound' => Layout::table('outboundCalls', $this->callColumns(showExclude: true)),
                'Staff' => Layout::table('staffNumbers', [
                    TD::make('name', 'Name')
                        ->render(fn (RingCentralStaffNumber $number): string => '<span class="fw-semibold">'.e($number->name).'</span>'),
                    TD::make('phone', 'Number')
                        ->render(fn (RingCentralStaffNumber $number): string => '<a class="fw-semibold" href="tel:'
                            .e($number->phone).'">'.e($number->phone).'</a>'),
                    TD::make('calls_count', 'Calls')
                        ->render(fn (RingCentralStaffNumber $number): string => $this->historyHtml(
                            (int) $number->getAttribute('person_call_count'),
                            $number->getAttribute('person_first_call_at'),
                        )),
                    TD::make('marked_at', 'Marked')
                        ->render(function (RingCentralStaffNumber $number): string {
                            $date = CarbonImmutable::parse($number->marked_at)
                                ->setTimezone('America/Los_Angeles');

                            return e($date->format('M d, Y h:i A')).' PT'
                                .'<div class="small text-muted">'.e($number->markedBy?->name ?? 'System').'</div>';
                        }),
                    TD::make('id', '')
                        ->align(TD::ALIGN_CENTER)
                        ->render(fn (RingCentralStaffNumber $number) => Button::make('Return to calls')
                            ->icon('bs.arrow-counterclockwise')
                            ->method('returnStaff', ['staff' => $number->id])
                            ->confirm('Put '.$number->name.' back into the inbound and outbound lists?')),
                ]),
                'Excluded numbers' => Layout::table('excludedNumbers', [
                    TD::make('phone', 'Number')
                        ->render(fn (RingCentralExcludedNumber $number): string => '<a class="fw-semibold" href="tel:'
                            .e($number->phone).'">'.e($number->phone).'</a>'),
                    TD::make('excluded_at', 'Excluded')
                        ->render(function (RingCentralExcludedNumber $number): string {
                            $date = CarbonImmutable::parse($number->excluded_at)
                                ->setTimezone('America/Los_Angeles');

                            return e($date->format('M d, Y h:i A')).' PT';
                        }),
                    TD::make('excluded_by', 'By')
                        ->render(fn (RingCentralExcludedNumber $number): string => e($number->excludedBy?->name ?? 'System')),
                    TD::make('hidden_calls_count', 'Hidden calls')
                        ->align(TD::ALIGN_CENTER)
                        ->render(fn (RingCentralExcludedNumber $number): string => (string) $number->hiddenCallsCount()),
                    TD::make('id', '')
                        ->align(TD::ALIGN_CENTER)
                        ->render(fn (RingCentralExcludedNumber $number) => Button::make('Restore')
                            ->icon('bs.arrow-counterclockwise')
                            ->method('restoreNumber', ['exclusion' => $number->id])
                            ->confirm('Return this number and all its calls to the lists?')),
                ]),
                'Other numbers' => Layout::table('otherCalls', $this->callColumns(showExclude: true)),
            ]),
            Layout::modal('markStaffModal', Layout::rows([
                Input::make('call')->type('hidden'),
                Input::make('phone')->title('Number')->readonly(),
                Input::make('staff_name')
                    ->title('Name')
                    ->required()
                    ->max(120)
                    ->help('Who this number belongs to. Their calls leave the inbound and outbound lists.'),
            ]))
                ->title('Mark as staff')
                ->applyButton('Save')
                ->deferred('loadStaffModal'),
        ];
    }

    /**
     * @return list<TD>
     */
    private function callColumns(bool $showExclude): array
    {
        $columns = [
            TD::make('started_at', 'Date')
                ->sort()
                ->width('190px')
                ->render(fn (RingCentralCall $call): string => $this->dateHtml($call)),
            TD::make('external_phone', 'Other number')
                ->render(function (RingCentralCall $call): string {
                    $name = $call->direction === 'Inbound' ? $call->from_name : $call->to_name;
                    $html = '<a class="fw-semibold" href="tel:'.e($call->external_phone).'">'
                        .e((string) $call->external_phone).'</a>'
                        .(filled($name) ? '<div class="small text-muted">'.e($name).'</div>' : '');
                    $html .= $this->associationHtml($call);

                    return $html;
                }),
            TD::make('route', 'From → To')
                ->render(fn (RingCentralCall $call): string => '<div>'.e((string) ($call->from_phone ?: '—')).'</div>'
                    .'<div class="small text-muted">→ '.e((string) ($call->to_phone ?: '—')).'</div>'
                    .'<div class="small text-muted mt-1">Line '.e((string) ($call->business_phone ?: '—')).'</div>'),
            TD::make('result', 'Result')
                ->render(fn (RingCentralCall $call): string => e((string) ($call->result ?: 'Unknown'))
                    .'<div class="small text-muted">'.e($call->durationLabel()).'</div>'),
            TD::make('handling_status', 'Status')
                ->sort()
                ->cantHide()
                ->width('180px')
                ->render(fn (RingCentralCall $call) => view('admin.ringcentral-calls.handling-status-cell', [
                    'call' => $call,
                ])),
        ];

        if ($showExclude) {
            $columns[] = TD::make('id', '')
                ->align(TD::ALIGN_CENTER)
                ->width('240px')
                ->render(fn (RingCentralCall $call) => view('admin.ringcentral-calls.call-actions', [
                    'call' => $call,
                ]));
        }

        return $columns;
    }

    public function addNote(Request $request): void
    {
        $validated = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:ringcentral_calls,id'],
            'note' => ['required', 'string', 'min:1', 'max:5000'],
        ]);

        $user = Auth::user();
        abort_unless($user !== null, 403);

        $call = RingCentralCall::query()
            ->visible()
            ->findOrFail((int) $validated['subject_id']);

        CrmNote::query()->create([
            'subject_type' => $call->getMorphClass(),
            'subject_id' => $call->id,
            'user_id' => $user->id,
            'body' => trim($validated['note']),
        ]);

        Toast::success('RingCentral call note saved.');
    }

    public function changeHandlingStatus(Request $request): void
    {
        $validated = $request->validate([
            'call' => ['required', 'integer', 'exists:ringcentral_calls,id'],
            'handling_status' => [
                'required',
                'string',
                \Illuminate\Validation\Rule::in(array_keys(RingCentralCall::HANDLING_STATUSES)),
            ],
        ]);

        $user = Auth::user();
        abort_unless($user !== null, 403);

        $call = RingCentralCall::query()
            ->visible()
            ->findOrFail((int) $validated['call']);
        $from = (string) $call->handling_status;
        $to = $validated['handling_status'];

        if ($from === $to) {
            Toast::info('Handling status unchanged.');

            return;
        }

        $call->forceFill([
            'handling_status' => $to,
            'handled_at' => now(),
            'handled_by' => $user->id,
        ])->save();

        Toast::success('Handling status: '.$call->handlingStatusLabel());
    }

    public function syncNow(RingCentralCallSyncService $sync): void
    {
        if (! Schema::hasTable('ringcentral_calls') || ! Schema::hasTable('ringcentral_call_sync_states')) {
            Toast::error('RingCentral call tables are missing. Run: php artisan migrate');

            return;
        }

        try {
            $result = $sync->sync(forceDays: 7);
        } catch (Throwable $exception) {
            report($exception);
            Toast::error('RingCentral sync failed: '.$exception->getMessage());

            return;
        }

        Toast::success(sprintf(
            'Synced account log: %d new, %d updated (%d fetched). Window %s → %s PT.',
            $result['created'],
            $result['updated'],
            $result['fetched'],
            CarbonImmutable::parse($result['from'])->setTimezone('America/Los_Angeles')->format('M d, h:i A'),
            CarbonImmutable::parse($result['to'])->setTimezone('America/Los_Angeles')->format('M d, h:i A'),
        ));
    }

    public function excludeNumber(
        Request $request,
        PromotionControlService $promotions,
        RingCentralCallLogService $callLog
    ): void {
        $validated = $request->validate([
            'call' => ['required', 'integer', 'exists:ringcentral_calls,id'],
        ]);
        $user = Auth::user();
        abort_unless($user !== null, 403);

        $call = RingCentralCall::query()->findOrFail((int) $validated['call']);
        $phone = $callLog->normalizePhone((string) $call->external_phone);
        $monitored = array_map(
            fn (string $value): string => $callLog->normalizePhone($value),
            $promotions->ringCentralPhones(),
        );

        if ($phone === '' || in_array($phone, $monitored, true)) {
            Toast::error('Monitored business numbers cannot be excluded.');

            return;
        }

        $exclusion = RingCentralExcludedNumber::query()->firstOrNew(['phone' => $phone]);
        $exclusion->excluded_at = now();
        $exclusion->excluded_by_user_id = $user->id;
        $exclusion->restored_at = null;
        $exclusion->restored_by_user_id = null;
        $exclusion->save();

        Toast::info($phone.' was excluded.');
    }

    public function restoreNumber(Request $request): void
    {
        $validated = $request->validate([
            'exclusion' => ['required', 'integer', 'exists:ringcentral_excluded_numbers,id'],
        ]);
        $user = Auth::user();
        abort_unless($user !== null, 403);

        $exclusion = RingCentralExcludedNumber::query()->findOrFail((int) $validated['exclusion']);
        $exclusion->restored_at = now();
        $exclusion->restored_by_user_id = $user->id;
        $exclusion->save();

        Toast::info($exclusion->phone.' was restored.');
    }

    public function loadStaffModal(RingCentralCall $call): iterable
    {
        $phone = app(RingCentralCallLogService::class)->normalizePhone((string) $call->external_phone);
        $existing = $phone === ''
            ? null
            : RingCentralStaffNumber::query()->where('phone', $phone)->first();

        return [
            'call' => $call->id,
            'phone' => $phone !== '' ? $phone : (string) $call->external_phone,
            'staff_name' => (string) ($existing->name ?? ''),
        ];
    }

    public function markStaff(
        Request $request,
        PromotionControlService $promotions,
        RingCentralCallLogService $callLog
    ): void {
        $validated = $request->validate([
            'call' => ['required', 'integer', 'exists:ringcentral_calls,id'],
            'staff_name' => ['required', 'string', 'min:1', 'max:120'],
        ]);
        $user = Auth::user();
        abort_unless($user !== null, 403);

        $call = RingCentralCall::query()->findOrFail((int) $validated['call']);
        $phone = $callLog->normalizePhone((string) $call->external_phone);
        $monitored = array_map(
            fn (string $value): string => $callLog->normalizePhone($value),
            $promotions->ringCentralPhones(),
        );

        if ($phone === '' || in_array($phone, $monitored, true)) {
            Toast::error('Monitored business numbers cannot be marked as staff.');

            return;
        }

        $staff = RingCentralStaffNumber::query()->firstOrNew(['phone' => $phone]);
        $staff->name = trim($validated['staff_name']);
        $staff->marked_at = now();
        $staff->marked_by_user_id = $user->id;
        $staff->restored_at = null;
        $staff->restored_by_user_id = null;
        $staff->save();

        Toast::info($staff->name.' ('.$phone.') was marked as staff.');
    }

    public function returnStaff(Request $request): void
    {
        $validated = $request->validate([
            'staff' => ['required', 'integer', 'exists:ringcentral_staff_numbers,id'],
        ]);
        $user = Auth::user();
        abort_unless($user !== null, 403);

        $staff = RingCentralStaffNumber::query()->findOrFail((int) $validated['staff']);
        $staff->restored_at = now();
        $staff->restored_by_user_id = $user->id;
        $staff->save();

        Toast::info($staff->name.' was returned to the call lists.');
    }

    private function presentCalls(LengthAwarePaginator $page): LengthAwarePaginator
    {
        $calls = $page->getCollection();
        if ($calls->isEmpty()) {
            return $page;
        }

        $phones = $calls->pluck('external_phone')->filter()->unique()->values();
        $stats = $this->phoneStats($phones);
        $matches = $this->matchesForPhones($phones);

        $calls->each(function (RingCentralCall $call) use ($stats, $matches): void {
            $stat = $stats->get((string) $call->external_phone);
            $call->setAttribute('person_call_count', (int) ($stat->calls_count ?? 1));
            $call->setAttribute('person_first_call_at', $stat->first_call_at ?? $call->getRawOriginal('started_at'));
            $suffix = $this->phoneSuffix((string) $call->external_phone);
            $call->setAttribute('matched_leads', $matches['leads']->get($suffix, collect()));
            $call->setAttribute('matched_contacts', $matches['contacts']->get($suffix, collect()));
        });

        $this->attachCallbacks($calls);

        return $page;
    }

    /**
     * @param  Collection<int, RingCentralStaffNumber>  $staff
     */
    private function presentStaff(Collection $staff): void
    {
        if ($staff->isEmpty()) {
            return;
        }

        $stats = $this->phoneStats($staff->pluck('phone')->filter()->unique()->values());
        $staff->each(function (RingCentralStaffNumber $number) use ($stats): void {
            $stat = $stats->get((string) $number->phone);
            $number->setAttribute('person_call_count', (int) ($stat->calls_count ?? 0));
            $number->setAttribute('person_first_call_at', $stat->first_call_at ?? null);
        });
    }

    /**
     * @param  Collection<int, string>  $phones
     * @return Collection<string, object>
     */
    private function phoneStats(Collection $phones): Collection
    {
        if ($phones->isEmpty()) {
            return collect();
        }

        return RingCentralCall::query()
            ->selectRaw('external_phone, COUNT(*) as calls_count, MIN(started_at) as first_call_at')
            ->whereIn('external_phone', $phones->all())
            ->groupBy('external_phone')
            ->get()
            ->keyBy('external_phone');
    }

    /**
     * @param  Collection<int, string>  $phones
     * @return array{leads: Collection<string, Collection<int, Lead>>, contacts: Collection<string, Collection<int, Contact>>}
     */
    private function matchesForPhones(Collection $phones): array
    {
        $suffixes = $phones
            ->map(fn (mixed $phone): string => $this->phoneSuffix((string) $phone))
            ->filter(fn (string $suffix): bool => strlen($suffix) === 10)
            ->unique()
            ->values();

        if ($suffixes->isEmpty()) {
            return ['leads' => collect(), 'contacts' => collect()];
        }

        $leads = Lead::query()
            ->where('status', '!=', Lead::STATUS_SPAM)
            ->where(function ($query) use ($suffixes): void {
                foreach ($suffixes as $suffix) {
                    $query->orWhere('normalized_phone', 'like', '%'.$suffix);
                }
            })
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->groupBy(fn (Lead $lead): string => $this->phoneSuffix((string) $lead->normalized_phone));

        $contacts = Contact::query()
            ->where(function ($query) use ($suffixes): void {
                foreach ($suffixes as $suffix) {
                    $query->orWhere('normalized_phone', 'like', '%'.$suffix);
                }
            })
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->groupBy(fn (Contact $contact): string => $this->phoneSuffix((string) $contact->normalized_phone));

        return ['leads' => $leads, 'contacts' => $contacts];
    }

    private function associationHtml(RingCentralCall $call): string
    {
        $html = '';
        $seenContacts = [];
        if ($call->contact_id && $call->contact) {
            $seenContacts[] = (int) $call->contact_id;
            $html .= '<div class="small mt-1"><a href="'
                .e(route('platform.contacts.edit', $call->contact)).'">'
                .e($call->contact->full_name ?: 'Contact #'.$call->contact_id)
                .'</a></div>';
        }

        $extra = collect($call->getAttribute('matched_contacts') ?? [])
            ->reject(fn (Contact $contact): bool => in_array((int) $contact->id, $seenContacts, true))
            ->take(2);
        foreach ($extra as $contact) {
            $html .= '<div class="small"><a href="'.e(route('platform.contacts.edit', $contact)).'">'
                .e($contact->full_name ?: 'Contact #'.$contact->id).'</a></div>';
        }

        foreach (collect($call->getAttribute('matched_leads') ?? [])->take(3) as $lead) {
            $html .= '<div class="small"><a href="'.e(route('platform.leads.edit', $lead)).'">Lead: '
                .e($lead->full_name ?: '#'.$lead->id).'</a>'
                .' <span class="text-muted">'.e($lead->statusLabel()).'</span></div>';
        }

        if ($html === '') {
            return '<div class="small text-muted">No contact or lead</div>';
        }

        return $html;
    }

    private function dateHtml(RingCentralCall $call): string
    {
        $date = $call->startedAtPacific();
        $when = $date === null
            ? '<div class="fw-semibold">—</div>'
            : '<div class="fw-semibold">'.e($date->format('M d, Y')).'</div>'
                .'<div class="small text-muted">'.e($date->format('h:i A')).' PT</div>';

        $connected = app(RingCentralPhoneCallStatsService::class)->isConnectedCall($call);
        $badge = $connected
            ? '<span class="badge bg-success text-white">Принят</span>'
            : '<span class="badge bg-danger text-white">Missed</span>';

        $callback = '';
        if ($call->direction === 'Inbound' && ! $connected) {
            $back = $call->getAttribute('called_back_at');
            if ($back instanceof \DateTimeInterface) {
                $backAt = CarbonImmutable::parse($back)->setTimezone('America/Los_Angeles');
                $callback = '<div class="small text-success">Перезвонили '.e($backAt->format('M d, h:i A')).'</div>';
            } else {
                $callback = '<div class="small text-danger">Не перезвонили</div>';
            }
        }

        $count = (int) $call->getAttribute('person_call_count');
        $countLabel = $count === 1 ? '1 call' : $count.' calls';
        $first = '—';
        $firstAt = $call->getAttribute('person_first_call_at');
        if ($firstAt !== null && $firstAt !== '') {
            $first = CarbonImmutable::parse((string) $firstAt, 'UTC')
                ->setTimezone('America/Los_Angeles')
                ->format('M d, Y');
        }

        return $when
            .'<div class="mt-1">'.$badge.'</div>'
            .$callback
            .'<div class="small text-muted mt-1">First '.e($first).'</div>'
            .'<div class="small text-muted">'.e($countLabel).'</div>';
    }

    /**
     * @param  Collection<int, RingCentralCall>  $calls
     */
    private function attachCallbacks(Collection $calls): void
    {
        $missed = $calls->filter(function (RingCentralCall $call): bool {
            return $call->direction === 'Inbound'
                && ! app(RingCentralPhoneCallStatsService::class)->isConnectedCall($call);
        });
        $phones = $missed->pluck('external_phone')->filter()->unique()->values();
        if ($phones->isEmpty()) {
            return;
        }

        $outbound = RingCentralCall::query()
            ->where('direction', 'Outbound')
            ->whereIn('external_phone', $phones->all())
            ->orderBy('started_at')
            ->get(['id', 'external_phone', 'started_at']);
        $byPhone = $outbound->groupBy('external_phone');

        $missed->each(function (RingCentralCall $call) use ($byPhone): void {
            $later = $byPhone->get((string) $call->external_phone, collect())
                ->first(function (RingCentralCall $candidate) use ($call): bool {
                    $candidateAt = $candidate->started_at;
                    $callAt = $call->started_at;

                    return $candidateAt !== null
                        && $callAt !== null
                        && $candidate->id !== $call->id
                        && $candidateAt->greaterThan($callAt);
                });
            $call->setAttribute('called_back_at', $later?->started_at);
        });
    }

    private function historyHtml(int $count, mixed $firstAt): string
    {
        $first = '—';
        if ($firstAt !== null && $firstAt !== '') {
            $first = CarbonImmutable::parse((string) $firstAt, 'UTC')
                ->setTimezone('America/Los_Angeles')
                ->format('M d, Y');
        }

        $label = $count === 1 ? '1 call' : $count.' calls';

        return '<div class="fw-semibold">'.e($label).'</div>'
            .'<div class="small text-muted">First '.e($first).'</div>';
    }

    private function phoneSuffix(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return strlen($digits) >= 10 ? substr($digits, -10) : $digits;
    }

    private function resolveLastSyncedLabel(): string
    {
        if (! Schema::hasTable('ringcentral_call_sync_states')) {
            return 'never';
        }

        $state = RingCentralCallSyncState::query()
            ->where('business_phone', RingCentralCallSyncService::ACCOUNT_SYNC_KEY)
            ->whereNotNull('last_synced_at')
            ->first();

        if ($state === null && $this->monitoredPhones !== []) {
            $state = RingCentralCallSyncState::query()
                ->whereIn('business_phone', $this->monitoredPhones)
                ->whereNotNull('last_synced_at')
                ->orderByDesc('last_synced_at')
                ->first();
        }

        if ($state === null) {
            return 'never';
        }

        $raw = $state->getRawOriginal('last_synced_at');
        if ($raw === null || $raw === '') {
            return 'never';
        }

        return CarbonImmutable::createFromFormat('Y-m-d H:i:s', (string) $raw, 'UTC')
            ->setTimezone('America/Los_Angeles')
            ->format('M d, Y h:i A').' PT';
    }
}
