<?php

declare(strict_types=1);

namespace App\Orchid\Screens\PhoneClicks;

use App\Models\PhoneClick;
use App\Services\PhoneClickGoogleBridge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\Sight;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class PhoneClickViewScreen extends Screen
{
    public ?PhoneClick $click = null;

    public function query(PhoneClick $click): iterable
    {
        $click->load('googleSheetSender');
        $this->click = $click;

        return [
            'click' => $click,
        ];
    }

    public function name(): ?string
    {
        return $this->click
            ? 'Phone click #'.$this->click->id
            : 'Phone click';
    }

    public function description(): ?string
    {
        return 'Click-to-call details and attribution.';
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
                ->route('platform.phone-clicks'),
        ];

        if ($this->click && ! $this->click->wasSentToGoogleSheet()) {
            $actions[] = Button::make('Send to Google')
                ->icon('bs.google')
                ->type(Color::PRIMARY)
                ->method('sendToGoogle', ['click' => $this->click->id])
                ->confirm('Send this phone click to the Google Sheet? This can only be done once.');
        }

        return $actions;
    }

    public function layout(): iterable
    {
        return [
            Layout::columns([
                Layout::legend('click', [
                    Sight::make('id', 'ID'),
                    Sight::make('created_at', 'Clicked')
                        ->render(fn (PhoneClick $click) => optional($click->created_at)->format('Y-m-d H:i:s')),
                    Sight::make('ringcentral_status', 'RingCentral status')
                        ->render(function (PhoneClick $click): string {
                            return match ($click->ringcentral_status) {
                                PhoneClick::RINGCENTRAL_FOUND => '<span class="badge bg-success text-white">✓ Call found</span>',
                                PhoneClick::RINGCENTRAL_NO_CALL => '<span class="badge bg-secondary text-white">No call found</span>',
                                PhoneClick::RINGCENTRAL_ERROR => '<span class="badge bg-danger text-white">Check failed</span>',
                                PhoneClick::RINGCENTRAL_PENDING => '<span class="badge bg-info text-dark">Checking…</span>',
                                default => '<span class="badge bg-light text-dark">Not checked</span>',
                            };
                        }),
                    Sight::make('ringcentral_checked_at', 'RingCentral checked')
                        ->render(fn (PhoneClick $click) => $click->ringcentral_checked_at
                            ? $click->ringcentral_checked_at
                                ->copy()
                                ->setTimezone('America/Los_Angeles')
                                ->format('Y-m-d H:i:s T')
                            : '-'),
                    Sight::make('ringcentral_call_started_at', 'Call started')
                        ->render(fn (PhoneClick $click) => $click->ringcentral_call_started_at
                            ? $click->ringcentral_call_started_at
                                ->copy()
                                ->setTimezone('America/Los_Angeles')
                                ->format('Y-m-d H:i:s T')
                            : '-'),
                    Sight::make('ringcentral_result', 'Call result')
                        ->render(fn (PhoneClick $click) => e((string) ($click->ringcentral_result ?: '-'))),
                    Sight::make('ringcentral_duration', 'Call duration')
                        ->render(fn (PhoneClick $click) => $click->ringcentral_call_id
                            ? e($click->ringCentralDurationLabel())
                            : '-'),
                    Sight::make('ringcentral_from_phone', 'Caller')
                        ->render(function (PhoneClick $click): string {
                            $phone = trim((string) $click->ringcentral_from_phone);

                            return $phone !== ''
                                ? '<a href="tel:'.e($phone).'">'.e($phone).'</a>'
                                : '-';
                        }),
                    Sight::make('ringcentral_to_phone', 'Called number')
                        ->render(fn (PhoneClick $click) => e((string) ($click->ringcentral_to_phone ?: '-'))),
                    Sight::make('ringcentral_attempts', 'RingCentral attempts'),
                    Sight::make('ringcentral_call_id', 'RingCentral call ID')
                        ->render(fn (PhoneClick $click) => e((string) ($click->ringcentral_call_id ?: '-'))),
                    Sight::make('ringcentral_error', 'RingCentral error')
                        ->render(fn (PhoneClick $click) => e((string) ($click->ringcentral_error ?: '-'))),
                    Sight::make('google_sheet_sent_at', 'Google Sheet')
                        ->render(function (PhoneClick $click): string {
                            if (! $click->wasSentToGoogleSheet()) {
                                return '<span class="badge bg-secondary text-white">Not sent</span>';
                            }

                            $sentAt = optional($click->google_sheet_sent_at)->format('Y-m-d H:i:s');
                            $sender = $click->googleSheetSender?->name;
                            $suffix = $sender ? ' by '.e($sender) : '';

                            return '<span class="badge bg-success text-white">✓ Sent '.e((string) $sentAt).$suffix.'</span>';
                        }),
                    Sight::make('phone', 'Phone')
                        ->render(function (PhoneClick $click): string {
                            $phone = trim((string) ($click->phone ?? ''));
                            if ($phone === '') {
                                return '-';
                            }

                            return '<a href="tel:'.e($phone).'">'.e($phone).'</a>';
                        }),
                    Sight::make('source_label', 'Source')
                        ->render(fn (PhoneClick $click) => e((string) ($click->source_label ?: '-'))),
                    Sight::make('page_url', 'Page')
                        ->render(function (PhoneClick $click): string {
                            $url = trim((string) ($click->page_url ?? ''));
                            if ($url === '') {
                                return '-';
                            }

                            return '<a href="'.e($url).'" target="_blank" rel="noopener">'.e($url).'</a>';
                        }),
                    Sight::make('landing_page', 'Landing page')
                        ->render(fn (PhoneClick $click) => e((string) ($click->landing_page ?: '-'))),
                    Sight::make('referrer', 'Referrer')
                        ->render(fn (PhoneClick $click) => e((string) ($click->referrer ?: '-'))),
                    Sight::make('geo_location', 'Geo')
                        ->render(fn (PhoneClick $click) => e((string) ($click->geo_location ?: '-'))),
                    Sight::make('ip_address', 'IP')
                        ->render(fn (PhoneClick $click) => e((string) ($click->ip_address ?: '-'))),
                    Sight::make('user_agent', 'User agent')
                        ->render(fn (PhoneClick $click) => e((string) ($click->user_agent ?: '-'))),
                ]),

                Layout::legend('click', [
                    Sight::make('utm_source', 'UTM source')
                        ->render(fn (PhoneClick $click) => e((string) ($click->utm_source ?: '-'))),
                    Sight::make('utm_medium', 'UTM medium')
                        ->render(fn (PhoneClick $click) => e((string) ($click->utm_medium ?: '-'))),
                    Sight::make('utm_campaign', 'UTM campaign')
                        ->render(fn (PhoneClick $click) => e((string) ($click->utm_campaign ?: '-'))),
                    Sight::make('utm_content', 'UTM content')
                        ->render(fn (PhoneClick $click) => e((string) ($click->utm_content ?: '-'))),
                    Sight::make('utm_term', 'UTM term')
                        ->render(fn (PhoneClick $click) => e((string) ($click->utm_term ?: '-'))),
                    Sight::make('matchtype', 'Match type')
                        ->render(fn (PhoneClick $click) => e((string) ($click->matchtype ?: '-'))),
                    Sight::make('device', 'Device')
                        ->render(fn (PhoneClick $click) => e((string) ($click->device ?: '-'))),
                    Sight::make('creative', 'Creative')
                        ->render(fn (PhoneClick $click) => e((string) ($click->creative ?: '-'))),
                    Sight::make('gclid', 'GCLID')
                        ->render(fn (PhoneClick $click) => e((string) ($click->gclid ?: '-'))),
                    Sight::make('fbclid', 'FBCLID')
                        ->render(fn (PhoneClick $click) => e((string) ($click->fbclid ?: '-'))),
                    Sight::make('msclkid', 'MSCLKID')
                        ->render(fn (PhoneClick $click) => e((string) ($click->msclkid ?: '-'))),
                ]),
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
