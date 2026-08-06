<?php

declare(strict_types=1);

namespace App\Orchid\Screens\PhoneClicks;

use App\Jobs\MatchPhoneClickToRingCentral;
use App\Jobs\SendPhoneClickOfflineConversions;
use App\Models\PhoneClick;
use App\Orchid\Screens\Concerns\QueuesCallTranscripts;
use App\Services\PhoneClickGoogleBridge;
use App\Services\RingCentralCallLogService;
use App\Services\TrafficSourceVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\Sight;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Throwable;

class PhoneClickViewScreen extends Screen
{
    use QueuesCallTranscripts;

    public ?PhoneClick $click = null;

    public function query(PhoneClick $click, TrafficSourceVisibility $visibility): iterable
    {
        $visibility->authorizeOrAbort(
            Auth::user(),
            $click,
            TrafficSourceVisibility::SECTION_PHONE_CLICKS
        );

        $click->load('googleSheetSender');
        $this->click = $click;

        return [
            'click' => $click,
            'matchedCall' => $click->ringCentralCall(),
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
            'platform.phone-clicks',
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

        if ($this->click) {
            $actions[] = Button::make('Re-match RingCentral')
                ->icon('bs.link-45deg')
                ->method('rematch', ['click' => $this->click->id])
                ->confirm('Clear current match (if any) and match this click again: same DID, call after click time?');
        }

        if ($this->click && $this->click->ringcentral_status === PhoneClick::RINGCENTRAL_FOUND) {
            $actions[] = Button::make('Resend ad conversions')
                ->icon('bs.megaphone')
                ->method('resendOfflineConversions', ['click' => $this->click->id])
                ->confirm('Upload this confirmed call to Google Ads and Microsoft Ads again?');
        }

        if ($this->click) {
            if ($this->click->isSpam()) {
                $actions[] = Button::make('Restore from spam')
                    ->icon('bs.arrow-counterclockwise')
                    ->method('restoreFromSpam', ['phone_click_id' => $this->click->id])
                    ->confirm('Move this phone click back to the main list?');
            } else {
                $actions[] = Button::make('Mark as spam')
                    ->icon('bs.shield-exclamation')
                    ->type(Color::DANGER)
                    ->method('markAsSpam', ['phone_click_id' => $this->click->id])
                    ->confirm('Mark this phone click as spam and hide it from the main list?');
            }
        }

        return $actions;
    }

    public function layout(): iterable
    {
        return [
            Layout::view('admin.phone-clicks.assets'),

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
                    Sight::make('recording', 'Recording')
                        ->render(fn (PhoneClick $click): string => $click->hasRecording()
                            ? view('admin.partials.call-recording', [
                                'url' => $click->recordingUrl(),
                            ])->render()
                            : '<span class="text-muted">No recording</span>'),
                    Sight::make('transcript', 'Transcript')
                        ->render(function (PhoneClick $click): string {
                            $call = $click->ringCentralCall();
                            if ($call === null) {
                                return '<span class="text-muted">No linked call journal row yet</span>';
                            }

                            return view('admin.partials.call-transcript', [
                                'call' => $call,
                                'compact' => false,
                                'canQueue' => true,
                            ])->render();
                        }),
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
                    Sight::make('google_ads_conversion_sent_at', 'Google Ads conversion')
                        ->render(fn (PhoneClick $click) => $this->conversionBadge(
                            $click->google_ads_conversion_sent_at,
                            (string) $click->google_ads_conversion_error,
                            $click->resolvedGclid() !== null,
                            'GCLID'
                        )),
                    Sight::make('bing_ads_conversion_sent_at', 'Microsoft Ads conversion')
                        ->render(fn (PhoneClick $click) => $this->conversionBadge(
                            $click->bing_ads_conversion_sent_at,
                            (string) $click->bing_ads_conversion_error,
                            $click->resolvedMsclkid() !== null,
                            'MSCLKID'
                        )),
                    Sight::make('phone', 'Phone')
                        ->render(function (PhoneClick $click): string {
                            $phone = trim((string) ($click->phone ?? ''));
                            if ($phone === '') {
                                return '-';
                            }

                            return '<a href="tel:'.e($phone).'">'.e($phone).'</a>';
                        }),
                    Sight::make('source_label', 'Click placement')
                        ->render(fn (PhoneClick $click) => e((string) ($click->source_label ?: '-'))),
                    Sight::make('utm_source', 'Last traffic source')
                        ->render(function (PhoneClick $click): string {
                            $detail = $click->trafficSourceDetail();

                            return '<span class="badge bg-'.$click->trafficSourceColor().' text-white">'
                                .e($click->trafficSourceLabel())
                                .'</span>'
                                .($detail !== ''
                                    ? '<div class="small text-muted mt-1">'.e($detail).'</div>'
                                    : '');
                        }),
                    Sight::make('first_utm_source', 'First traffic source')
                        ->render(function (PhoneClick $click): string {
                            $detail = $click->firstTrafficSourceDetail();

                            return '<span class="badge bg-'.$click->firstTrafficSourceColor().' text-white">'
                                .e($click->firstTrafficSourceLabel())
                                .'</span>'
                                .($detail !== ''
                                    ? '<div class="small text-muted mt-1">'.e($detail).'</div>'
                                    : '');
                        }),
                    Sight::make('page_url', 'Page')
                        ->render(function (PhoneClick $click): string {
                            $url = trim((string) ($click->page_url ?? ''));
                            if ($url === '') {
                                return '-';
                            }

                            return '<a href="'.e($url).'" target="_blank" rel="noopener">'.e($url).'</a>';
                        }),
                    Sight::make('landing_page', 'Last landing page')
                        ->render(fn (PhoneClick $click) => e((string) ($click->landing_page ?: '-'))),
                    Sight::make('first_landing_page', 'First landing page')
                        ->render(fn (PhoneClick $click) => e((string) ($click->first_landing_page ?: '-'))),
                    Sight::make('referrer', 'Last referrer')
                        ->render(fn (PhoneClick $click) => e((string) ($click->referrer ?: '-'))),
                    Sight::make('first_referrer', 'First referrer')
                        ->render(fn (PhoneClick $click) => e((string) ($click->first_referrer ?: '-'))),
                    Sight::make('geo_location', 'Geo')
                        ->render(fn (PhoneClick $click) => e((string) ($click->geo_location ?: '-'))),
                    Sight::make('ip_address', 'IP')
                        ->render(fn (PhoneClick $click) => e((string) ($click->ip_address ?: '-'))),
                    Sight::make('user_agent', 'User agent')
                        ->render(fn (PhoneClick $click) => e((string) ($click->user_agent ?: '-'))),
                ]),

                Layout::legend('click', [
                    Sight::make('utm_source', 'Last UTM source')
                        ->render(fn (PhoneClick $click) => e((string) ($click->utm_source ?: '-'))),
                    Sight::make('utm_medium', 'Last UTM medium')
                        ->render(fn (PhoneClick $click) => e((string) ($click->utm_medium ?: '-'))),
                    Sight::make('utm_campaign', 'Last UTM campaign')
                        ->render(fn (PhoneClick $click) => e((string) ($click->utm_campaign ?: '-'))),
                    Sight::make('gclid', 'Last GCLID')
                        ->render(fn (PhoneClick $click) => e((string) ($click->gclid ?: '-'))),
                    Sight::make('first_utm_source', 'First UTM source')
                        ->render(fn (PhoneClick $click) => e((string) ($click->first_utm_source ?: '-'))),
                    Sight::make('first_utm_medium', 'First UTM medium')
                        ->render(fn (PhoneClick $click) => e((string) ($click->first_utm_medium ?: '-'))),
                    Sight::make('first_utm_campaign', 'First UTM campaign')
                        ->render(fn (PhoneClick $click) => e((string) ($click->first_utm_campaign ?: '-'))),
                    Sight::make('first_gclid', 'First GCLID')
                        ->render(fn (PhoneClick $click) => e((string) ($click->first_gclid ?: '-'))),
                    Sight::make('utm_content', 'UTM content')
                        ->render(fn (PhoneClick $click) => e((string) ($click->utm_content ?: '-'))),
                    Sight::make('utm_term', 'UTM term')
                        ->render(fn (PhoneClick $click) => e((string) ($click->utm_term ?: '-'))),
                    Sight::make('utm_city', 'UTM city')
                        ->render(fn (PhoneClick $click) => e(
                            app(\App\Services\ServiceAreaRegions::class)->utmCityLabel($click->utm_city)
                        )),
                    Sight::make('utm_redirect', 'UTM redirect')
                        ->render(fn (PhoneClick $click) => e((string) ($click->utm_redirect ?: '-'))),
                    Sight::make('matchtype', 'Match type')
                        ->render(fn (PhoneClick $click) => e((string) ($click->matchtype ?: '-'))),
                    Sight::make('device', 'Device')
                        ->render(fn (PhoneClick $click) => e((string) ($click->device ?: '-'))),
                    Sight::make('creative', 'Creative')
                        ->render(fn (PhoneClick $click) => e((string) ($click->creative ?: '-'))),
                    Sight::make('fbclid', 'FBCLID')
                        ->render(fn (PhoneClick $click) => e((string) ($click->fbclid ?: '-'))),
                    Sight::make('msclkid', 'MSCLKID')
                        ->render(fn (PhoneClick $click) => e((string) ($click->msclkid ?: '-'))),
                ]),
            ]),
        ];
    }

    private function conversionBadge(
        ?\Illuminate\Support\Carbon $sentAt,
        string $error,
        bool $hasClickId,
        string $clickIdLabel
    ): string {
        if ($sentAt !== null) {
            return '<span class="badge bg-success text-white">✓ Sent '
                .e($sentAt->copy()->setTimezone('America/Los_Angeles')->format('Y-m-d H:i:s'))
                .'</span>';
        }

        if (trim($error) !== '') {
            return '<span class="badge bg-danger text-white">Failed</span>'
                .'<div class="small text-muted mt-1">'.e($error).'</div>';
        }

        if (! $hasClickId) {
            return '<span class="badge bg-light text-dark">No '.e($clickIdLabel).'</span>';
        }

        return '<span class="badge bg-secondary text-white">Not sent</span>';
    }

    public function resendOfflineConversions(Request $request): void
    {
        $validated = $request->validate([
            'click' => ['required', 'integer', 'exists:phone_clicks,id'],
        ]);

        $click = PhoneClick::query()->findOrFail((int) $validated['click']);

        if ($click->ringcentral_status !== PhoneClick::RINGCENTRAL_FOUND) {
            Toast::error('Match a RingCentral call first — offline conversions need a confirmed call.');

            return;
        }

        SendPhoneClickOfflineConversions::dispatch($click->id, force: true);
        Toast::info('Offline conversion upload queued.');
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

    public function rematch(Request $request, RingCentralCallLogService $ringCentral): void
    {
        $validated = $request->validate([
            'click' => ['required', 'integer', 'exists:phone_clicks,id'],
        ]);

        $click = PhoneClick::query()->findOrFail((int) $validated['click']);

        try {
            (new MatchPhoneClickToRingCentral($click->id, force: true))->handle($ringCentral);
        } catch (Throwable $exception) {
            report($exception);
            Toast::error('Re-match failed: '.$exception->getMessage());

            return;
        }

        $click->refresh();
        $this->click = $click;

        if ($click->ringcentral_status === PhoneClick::RINGCENTRAL_FOUND) {
            Toast::success('Matched RingCentral call: '.($click->ringcentral_result ?: 'found'));

            return;
        }

        Toast::warning('No matching RingCentral call found in the click time window.');
    }

    public function markAsSpam(Request $request)
    {
        try {
            $validated = $request->validate([
                'phone_click_id' => ['required', 'integer', 'exists:phone_clicks,id'],
            ]);

            $click = PhoneClick::query()->findOrFail((int) $validated['phone_click_id']);
            $click->markAsSpam();
            Toast::info('Phone click marked as spam.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            Toast::error('Could not mark as spam: '.$exception->getMessage());

            return;
        }

        return redirect()->route('platform.phone-clicks');
    }

    public function restoreFromSpam(Request $request): void
    {
        try {
            $validated = $request->validate([
                'phone_click_id' => ['required', 'integer', 'exists:phone_clicks,id'],
            ]);

            $click = PhoneClick::query()->findOrFail((int) $validated['phone_click_id']);
            $click->restoreFromSpam();
            $this->click = $click->refresh();
            Toast::info('Phone click restored from spam.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            Toast::error('Could not restore from spam: '.$exception->getMessage());
        }
    }
}
