<?php

declare(strict_types=1);

namespace App\Orchid\Screens\PhoneClicks;

use App\Jobs\MatchPhoneClickToRingCentral;
use App\Models\PhoneClick;
use App\Services\PhoneClickGoogleBridge;
use App\Services\RingCentralCallLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Throwable;

class PhoneClickListScreen extends Screen
{
    public function query(): iterable
    {
        return [
            'clicks' => PhoneClick::query()
                ->defaultSort('id', 'desc')
                ->paginate(50),
        ];
    }

    public function name(): ?string
    {
        return 'Phone clicks';
    }

    public function description(): ?string
    {
        return 'Website phone clicks with RingCentral validation (main + extra numbers), GCLID attribution, and Google Sheet status.';
    }

    public function permission(): ?iterable
    {
        return [
            'platform.leads',
        ];
    }

    public function commandBar(): iterable
    {
        return [
            Button::make('Check RingCentral now')
                ->icon('bs.arrow-repeat')
                ->method('checkRingCentralNow')
                ->confirm('Re-check pending phone clicks against RingCentral (main and extra numbers)?'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('clicks', [
                TD::make('created_at', 'Date')
                    ->sort()
                    ->width('125px')
                    ->render(function (PhoneClick $click): string {
                        if (! $click->created_at) {
                            return '-';
                        }

                        return '<div class="fw-semibold">'.e($click->created_at->format('M d, Y')).'</div>'
                            .'<div class="small text-muted">'.e($click->created_at->format('h:i A')).'</div>';
                    }),

                TD::make('phone', 'Click')
                    ->width('175px')
                    ->render(function (PhoneClick $click): string {
                        $phone = trim((string) ($click->phone ?? ''));
                        $source = trim((string) ($click->source_label ?? ''));
                        $phoneHtml = $phone !== ''
                            ? '<a class="fw-semibold" href="tel:'.e($phone).'">'.e($phone).'</a>'
                            : '<span class="text-muted">No number</span>';

                        return $phoneHtml
                            .'<div class="small text-muted mt-1">'.e($source !== '' ? $source : 'Unknown source').'</div>';
                    }),

                TD::make('page_url', 'Page')
                    ->width('190px')
                    ->render(function (PhoneClick $click): string {
                        $url = trim((string) ($click->page_url ?? ''));
                        $landing = trim((string) ($click->landing_page ?? ''));

                        if ($url === '' && $landing === '') {
                            return '-';
                        }

                        $label = $url !== ''
                            ? ((string) parse_url($url, PHP_URL_PATH) ?: $url)
                            : $landing;
                        $page = $url !== ''
                            ? '<a href="'.e($url).'" target="_blank" rel="noopener">'.e(Str::limit($label, 30)).'</a>'
                            : e(Str::limit($label, 30));

                        if ($landing !== '' && $landing !== $label) {
                            $page .= '<div class="small text-muted mt-1">'.e(Str::limit($landing, 30)).'</div>';
                        }

                        return $page;
                    }),

                TD::make('gclid', 'GCLID')
                    ->width('145px')
                    ->render(function (PhoneClick $click): string {
                        $gclid = trim((string) ($click->gclid ?? ''));

                        return $gclid !== ''
                            ? '<code>'.e(Str::limit($gclid, 16, '…')).'</code>'
                            : '<span class="text-muted">—</span>';
                    }),

                TD::make('ringcentral_status', 'RingCentral')
                    ->sort()
                    ->cantHide()
                    ->width('220px')
                    ->render(function (PhoneClick $click): string {
                        $status = (string) ($click->ringcentral_status ?: PhoneClick::RINGCENTRAL_NOT_CHECKED);

                        if ($status === PhoneClick::RINGCENTRAL_FOUND) {
                            $result = trim((string) ($click->ringcentral_result ?: 'Call found'));

                            return '<span class="badge bg-success text-white">✓ '.e($result).'</span>'
                                .'<div class="small text-muted mt-1">'
                                .e($click->ringCentralDurationLabel())
                                .' · '.e((string) ($click->ringcentral_from_phone ?: 'Unknown caller'))
                                .'</div>';
                        }

                        if ($status === PhoneClick::RINGCENTRAL_NO_CALL) {
                            return '<span class="badge bg-secondary text-white">No call found</span>';
                        }

                        if ($status === PhoneClick::RINGCENTRAL_ERROR) {
                            return '<span class="badge bg-danger text-white">Check failed</span>';
                        }

                        if ($status === PhoneClick::RINGCENTRAL_PENDING) {
                            return '<span class="badge bg-info text-dark">Checking…</span>';
                        }

                        return '<span class="badge bg-light text-dark">Not checked</span>';
                    }),

                TD::make('google_sheet_sent_at', 'Google Sheet')
                    ->sort()
                    ->cantHide()
                    ->width('190px')
                    ->render(function (PhoneClick $click) {
                        if ($click->wasSentToGoogleSheet()) {
                            $sentAt = optional($click->google_sheet_sent_at)->format('Y-m-d H:i');

                            return '<span class="badge bg-success text-white">✓ Sent '.e((string) $sentAt).'</span>';
                        }

                        return Button::make('Send to Google')
                            ->icon('bs.google')
                            ->type(Color::PRIMARY)
                            ->method('sendToGoogle', ['click' => $click->id])
                            ->confirm('Send this phone click to the Google Sheet? This can only be done once.');
                    }),

                TD::make('id', '')
                    ->align(TD::ALIGN_CENTER)
                    ->width('80px')
                    ->render(fn (PhoneClick $click) => Link::make('View')
                        ->icon('bs.eye')
                        ->route('platform.phone-clicks.view', $click)),
            ]),
        ];
    }

    public function sendToGoogle(Request $request, PhoneClickGoogleBridge $bridge): void
    {
        $validated = $request->validate([
            'click' => ['required', 'integer', 'exists:phone_clicks,id'],
        ]);

        $user = Auth::user();
        abort_unless($user !== null, 403);

        $click = PhoneClick::query()->findOrFail((int) $validated['click']);
        $result = $bridge->sendOnce($click, (int) $user->id);

        if (! $result['ok']) {
            Toast::error($result['message']);

            return;
        }

        if ($result['already_sent']) {
            Toast::warning($result['message']);

            return;
        }

        Toast::success($result['message']);
    }

    public function checkRingCentralNow(RingCentralCallLogService $ringCentral): void
    {
        $clicks = PhoneClick::query()
            ->whereNotIn('ringcentral_status', [
                PhoneClick::RINGCENTRAL_FOUND,
                PhoneClick::RINGCENTRAL_NO_CALL,
            ])
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        if ($clicks->isEmpty()) {
            Toast::info('No pending phone clicks need a RingCentral check.');

            return;
        }

        $found = 0;
        $errors = 0;

        foreach ($clicks as $click) {
            // Allow an immediate admin re-check even if a job ran moments ago.
            $click->forceFill([
                'ringcentral_checked_at' => null,
            ])->saveQuietly();

            try {
                (new MatchPhoneClickToRingCentral($click->id))->handle($ringCentral);
            } catch (Throwable $exception) {
                report($exception);
                $errors++;
                continue;
            }

            if ($click->refresh()->ringcentral_status === PhoneClick::RINGCENTRAL_FOUND) {
                $found++;
            }
        }

        Toast::success(sprintf(
            'Checked %d phone click(s): %d matched, %d errors.',
            $clicks->count(),
            $found,
            $errors,
        ));
    }
}
