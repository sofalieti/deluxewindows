<?php

declare(strict_types=1);

namespace App\Orchid\Screens\RingCentralCalls;

use App\Models\RingCentralCall;
use App\Models\RingCentralExcludedNumber;
use App\Services\PromotionControlService;
use App\Services\RingCentralCallLogService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class RingCentralCallListScreen extends Screen
{
    private string $businessPhone = '';

    public function query(
        PromotionControlService $promotions,
        RingCentralCallLogService $callLog
    ): iterable {
        $this->businessPhone = $callLog->normalizePhone($promotions->phoneTel());

        return [
            'calls' => RingCentralCall::query()
                ->visible()
                ->defaultSort('started_at', 'desc')
                ->paginate(50),
            'excludedNumbers' => RingCentralExcludedNumber::query()
                ->whereNull('restored_at')
                ->select('ringcentral_excluded_numbers.*')
                ->selectSub(
                    RingCentralCall::query()
                        ->selectRaw('count(*)')
                        ->whereColumn('ringcentral_calls.external_phone', 'ringcentral_excluded_numbers.phone'),
                    'hidden_calls_count'
                )
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
        return 'Inbound and outbound calls for '.$this->businessPhone.'. Synced hourly.';
    }

    public function permission(): ?iterable
    {
        return ['platform.leads'];
    }

    public function commandBar(): iterable
    {
        return [];
    }

    public function layout(): iterable
    {
        return [
            Layout::view('admin.ringcentral-calls.assets'),
            Layout::tabs([
                'Calls' => Layout::table('calls', [
                    TD::make('started_at', 'Date')
                        ->sort()
                        ->width('145px')
                        ->render(function (RingCentralCall $call): string {
                            $date = CarbonImmutable::parse($call->started_at)
                                ->setTimezone('America/Los_Angeles');

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

                            return '<a class="fw-semibold" href="tel:'.e($call->external_phone).'">'
                                .e((string) $call->external_phone).'</a>'
                                .(filled($name) ? '<div class="small text-muted">'.e($name).'</div>' : '');
                        }),
                    TD::make('route', 'From → To')
                        ->render(fn (RingCentralCall $call): string => '<div>'.e((string) ($call->from_phone ?: '—')).'</div>'
                            .'<div class="small text-muted">→ '.e((string) ($call->to_phone ?: '—')).'</div>'),
                    TD::make('result', 'Result')
                        ->render(fn (RingCentralCall $call): string => e((string) ($call->result ?: 'Unknown'))
                            .'<div class="small text-muted">'.e($call->durationLabel()).'</div>'),
                    TD::make('id', '')
                        ->align(TD::ALIGN_CENTER)
                        ->width('125px')
                        ->render(fn (RingCentralCall $call) => Button::make('Exclude number')
                            ->icon('bs.eye-slash')
                            ->method('excludeNumber', ['call' => $call->id])
                            ->confirm('Hide all calls to and from '.$call->external_phone.'?')),
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
                        ->render(fn (RingCentralExcludedNumber $number): string => (string) $number->hidden_calls_count),
                    TD::make('id', '')
                        ->align(TD::ALIGN_CENTER)
                        ->render(fn (RingCentralExcludedNumber $number) => Button::make('Restore')
                            ->icon('bs.arrow-counterclockwise')
                            ->method('restoreNumber', ['exclusion' => $number->id])
                            ->confirm('Return this number and all its calls to the main list?')),
                ]),
            ]),
        ];
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
        $businessPhone = $callLog->normalizePhone($promotions->phoneTel());

        if ($phone === '' || $phone === $businessPhone) {
            Toast::error('The current business number cannot be excluded.');

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
}
