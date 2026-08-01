<?php

declare(strict_types=1);

namespace App\Orchid\Screens\RingCentralCalls;

use App\Models\RingCentralCall;
use App\Models\RingCentralCallSyncState;
use App\Models\RingCentralExcludedNumber;
use App\Services\PromotionControlService;
use App\Services\RingCentralCallLogService;
use App\Services\RingCentralCallSyncService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Throwable;

class RingCentralCallListScreen extends Screen
{
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

        if (! Schema::hasTable('ringcentral_calls') || ! Schema::hasTable('ringcentral_excluded_numbers')) {
            Toast::error('RingCentral call tables are missing. Run: php artisan migrate');

            return [
                'mainCalls' => new LengthAwarePaginator([], 0, 50, 1, ['pageName' => 'main_page']),
                'otherCalls' => new LengthAwarePaginator([], 0, 50, 1, ['pageName' => 'other_page']),
                'excludedNumbers' => collect(),
            ];
        }

        return [
            'mainCalls' => RingCentralCall::query()
                ->visible()
                ->onMonitoredLines($this->monitoredPhones)
                ->with('contact')
                ->defaultSort('started_at', 'desc')
                ->paginate(50, pageName: 'main_page'),
            'otherCalls' => RingCentralCall::query()
                ->visible()
                ->onOtherLines($this->monitoredPhones)
                ->with('contact')
                ->defaultSort('started_at', 'desc')
                ->paginate(50, pageName: 'other_page'),
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

        return 'Account-wide call log. Main numbers: '.$phones
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
                'Main numbers' => Layout::table('mainCalls', $this->callColumns(showExclude: true)),
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
                ->width('145px')
                ->render(function (RingCentralCall $call): string {
                    $date = $call->startedAtPacific();
                    if ($date === null) {
                        return '—';
                    }

                    return '<div class="fw-semibold">'.e($date->format('M d, Y')).'</div>'
                        .'<div class="small text-muted">'.e($date->format('h:i A')).' PT</div>';
                }),
            TD::make('direction', 'Direction')
                ->sort()
                ->render(fn (RingCentralCall $call): string => $call->direction === 'Inbound'
                    ? '<span class="badge bg-success text-white">Inbound</span>'
                    : '<span class="badge bg-primary text-white">Outbound</span>'),
            TD::make('external_phone', 'Other number')
                ->render(function (RingCentralCall $call): string {
                    $name = $call->direction === 'Inbound' ? $call->from_name : $call->to_name;
                    $html = '<a class="fw-semibold" href="tel:'.e($call->external_phone).'">'
                        .e((string) $call->external_phone).'</a>'
                        .(filled($name) ? '<div class="small text-muted">'.e($name).'</div>' : '');

                    if ($call->contact_id && $call->contact) {
                        $html .= '<div class="small mt-1"><a href="'
                            .e(route('platform.contacts.edit', $call->contact)).'">'
                            .e($call->contact->full_name ?: 'Contact #'.$call->contact_id)
                            .'</a></div>';
                    }

                    return $html;
                }),
            TD::make('route', 'From → To')
                ->render(fn (RingCentralCall $call): string => '<div>'.e((string) ($call->from_phone ?: '—')).'</div>'
                    .'<div class="small text-muted">→ '.e((string) ($call->to_phone ?: '—')).'</div>'
                    .'<div class="small text-muted mt-1">Line '.e((string) ($call->business_phone ?: '—')).'</div>'),
            TD::make('result', 'Result')
                ->render(fn (RingCentralCall $call): string => e((string) ($call->result ?: 'Unknown'))
                    .'<div class="small text-muted">'.e($call->durationLabel()).'</div>'),
        ];

        if ($showExclude) {
            $columns[] = TD::make('id', '')
                ->align(TD::ALIGN_CENTER)
                ->width('125px')
                ->render(fn (RingCentralCall $call) => Button::make('Exclude number')
                    ->icon('bs.eye-slash')
                    ->method('excludeNumber', ['call' => $call->id])
                    ->confirm('Hide all calls to and from '.$call->external_phone.'?'));
        }

        return $columns;
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
