<?php

declare(strict_types=1);

namespace App\Orchid\Screens\PhoneClicks;

use App\Models\PhoneClick;
use App\Services\PhoneClickGoogleBridge;
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
        return 'Click-to-call events from tel: links on the website, with UTM and landing data.';
    }

    public function permission(): ?iterable
    {
        return [
            'platform.leads',
        ];
    }

    public function commandBar(): iterable
    {
        return [];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('clicks', [
                TD::make('created_at', 'Date')
                    ->sort()
                    ->render(fn (PhoneClick $click) => optional($click->created_at)->format('Y-m-d H:i')),

                TD::make('phone', 'Phone')
                    ->render(function (PhoneClick $click): string {
                        $phone = trim((string) ($click->phone ?? ''));
                        if ($phone === '') {
                            return '-';
                        }

                        return '<a href="tel:'.e($phone).'">'.e($phone).'</a>';
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

                TD::make('source_label', 'Source')
                    ->render(fn (PhoneClick $click) => e((string) ($click->source_label ?: '-'))),

                TD::make('utm', 'UTM')
                    ->width('280px')
                    ->render(function (PhoneClick $click): string {
                        $parts = $click->utmSummaryParts();

                        return e($parts !== [] ? implode(' | ', $parts) : '-');
                    }),

                TD::make('landing_page', 'Landing')
                    ->render(fn (PhoneClick $click) => e(Str::limit((string) ($click->landing_page ?: '-'), 40))),

                TD::make('page_url', 'Page')
                    ->render(function (PhoneClick $click): string {
                        $url = trim((string) ($click->page_url ?? ''));
                        if ($url === '') {
                            return '-';
                        }

                        return '<a href="'.e($url).'" target="_blank" rel="noopener">'.e(Str::limit($url, 50)).'</a>';
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

                TD::make('id', 'Details')
                    ->render(fn (PhoneClick $click) => Link::make('Open')
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
}
