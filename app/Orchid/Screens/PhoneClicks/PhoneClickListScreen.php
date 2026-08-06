<?php

declare(strict_types=1);

namespace App\Orchid\Screens\PhoneClicks;

use App\Jobs\MatchPhoneClickToRingCentral;
use App\Models\PhoneClick;
use App\Services\Ads\GoogleAdsOfflineSheetExporter;
use App\Services\PhoneClickGoogleBridge;
use App\Services\RingCentralCallLogService;
use App\Services\TrafficSourceVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
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
        $user = Auth::user();

        return [
            'clicks' => PhoneClick::query()
                ->visibleTo($user)
                ->notSpam()
                ->defaultSort('id', 'desc')
                ->paginate(50, pageName: 'page'),
            'spamClicks' => PhoneClick::query()
                ->visibleTo($user)
                ->onlySpam()
                ->defaultSort('id', 'desc')
                ->paginate(50, pageName: 'spam_page'),
        ];
    }

    public function name(): ?string
    {
        return 'Phone clicks';
    }

    public function description(): ?string
    {
        $sources = app(TrafficSourceVisibility::class)
            ->allowedBucketLabels(Auth::user(), TrafficSourceVisibility::SECTION_PHONE_CLICKS);
        $sourceNote = $sources === []
            ? ' No traffic sources are enabled for your role.'
            : ' Visible sources: '.implode(', ', $sources).'.';

        return 'Website phone clicks with traffic source, RingCentral call tracking, and spam tab.'.$sourceNote;
    }

    public function permission(): ?iterable
    {
        return [
            'platform.phone-clicks',
        ];
    }

    public function commandBar(): iterable
    {
        return [
            Button::make('Export Google Ads sheet (yesterday)')
                ->icon('bs.file-earmark-spreadsheet')
                ->method('exportGoogleAdsSheetYesterday')
                ->confirm('Create a new Google Sheet in Drive with yesterday\'s RingCentral-confirmed phone clicks that have a GCLID?'),
            Button::make('Export Google Ads sheet (all pending)')
                ->icon('bs.cloud-upload')
                ->method('exportGoogleAdsSheetAllPending')
                ->confirm('Create a new Google Sheet with all confirmed phone clicks that still have a GCLID and have not been exported yet?'),
            Button::make('Check RingCentral now')
                ->icon('bs.arrow-repeat')
                ->method('checkRingCentralNow')
                ->confirm('Re-check pending / error phone clicks against the phone number that was clicked?'),
            Button::make('Re-run call tracking')
                ->icon('bs.link-45deg')
                ->method('rematchUnmatched')
                ->confirm('Reset and re-match the latest 100 non-spam phone clicks against each click phone number?'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::view('admin.phone-clicks.assets'),
            Layout::tabs([
                'Phone clicks' => Layout::table('clicks', $this->clickColumns(spamTab: false)),
                'Spam' => Layout::table('spamClicks', $this->clickColumns(spamTab: true)),
            ]),
        ];
    }

    /**
     * @return list<TD>
     */
    private function clickColumns(bool $spamTab): array
    {
        $columns = [
            TD::make('created_at', 'Click')
                ->sort()
                ->cantHide()
                ->width('160px')
                ->render(fn (PhoneClick $click) => view('admin.phone-clicks.click-cell', [
                    'click' => $click,
                ])),

            TD::make('page_url', 'Page')
                ->width('200px')
                ->render(fn (PhoneClick $click) => view('admin.phone-clicks.page-cell', [
                    'click' => $click,
                ])),

            TD::make('utm_source', 'Traffic')
                ->width('140px')
                ->render(fn (PhoneClick $click) => view('admin.phone-clicks.traffic-cell', [
                    'click' => $click,
                ])),

            TD::make('ringcentral_status', 'RingCentral')
                ->sort()
                ->cantHide()
                ->width('220px')
                ->render(function (PhoneClick $click): string {
                    $status = (string) ($click->ringcentral_status ?: PhoneClick::RINGCENTRAL_NOT_CHECKED);

                    if ($status === PhoneClick::RINGCENTRAL_FOUND) {
                        $result = trim((string) ($click->ringcentral_result ?: 'Call found'));
                        $callAt = $click->ringcentral_call_started_at
                            ? $click->ringcentral_call_started_at->copy()->timezone('America/Los_Angeles')->format('h:i A')
                            : null;
                        $lag = $click->metaValue('ringcentral_match_lag_seconds');
                        $lagLabel = $lag !== '' ? ' · +'.$lag.'s after click' : '';

                        $html = '<span class="badge bg-success text-white">✓ '.e($result).'</span>'
                            .'<div class="small text-muted mt-1">'
                            .($callAt ? 'Call '.e($callAt).' PT · ' : '')
                            .e($click->ringCentralDurationLabel())
                            .' · '.e((string) ($click->ringcentral_from_phone ?: 'Unknown caller'))
                            .e($lagLabel)
                            .'</div>';

                        if ($click->hasRecording()) {
                            $html .= view('admin.partials.call-recording', [
                                'url' => $click->recordingUrl(),
                                'compact' => true,
                            ])->render();
                        }

                        $matchedCall = $click->ringCentralCall();
                        if ($matchedCall !== null) {
                            $html .= view('admin.partials.call-transcript', [
                                'call' => $matchedCall,
                                'compact' => true,
                                'canQueue' => false,
                            ])->render();
                        }

                        return $html;
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

            TD::make('id', 'View')
                ->align(TD::ALIGN_CENTER)
                ->width('70px')
                ->render(fn (PhoneClick $click) => Link::make('')
                    ->icon('bs.eye')
                    ->route('platform.phone-clicks.view', $click)),
        ];

        $columns[] = TD::make('actions', '')
            ->align(TD::ALIGN_CENTER)
            ->width('120px')
            ->render(function (PhoneClick $click) use ($spamTab) {
                $spamAction = $spamTab
                    ? Button::make('Restore')
                        ->icon('bs.arrow-counterclockwise')
                        ->method('restoreFromSpam', ['phone_click_id' => $click->id])
                        ->confirm('Move this phone click back to the main list?')
                    : Button::make('Spam')
                        ->icon('bs.shield-exclamation')
                        ->type(Color::DANGER)
                        ->method('markAsSpam', ['phone_click_id' => $click->id])
                        ->confirm('Mark this phone click as spam and hide it from the main list?');

                $sendToGoogle = $click->ringCentralClientPhone() !== null
                    ? Button::make('Google')
                        ->icon('bs.google')
                        ->type(Color::PRIMARY)
                        ->method('sendToGoogle', ['click' => $click->id])
                        ->confirm('Send this phone click to the Google Sheet with the RingCentral client phone? This can only be done once.')
                    : null;

                return view('admin.phone-clicks.actions-cell', [
                    'click' => $click,
                    'sendToGoogle' => $sendToGoogle,
                    'spamAction' => $spamAction,
                ]);
            });

        return $columns;
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
        try {
            $this->runRingCentralMatch(
                PhoneClick::query()
                    ->notSpam()
                    ->whereNotIn('ringcentral_status', [
                        PhoneClick::RINGCENTRAL_FOUND,
                        PhoneClick::RINGCENTRAL_NO_CALL,
                    ])
                    ->orderByDesc('id')
                    ->limit(100)
                    ->get(),
                force: false,
                emptyMessage: 'No pending phone clicks need a RingCentral check.',
                ringCentral: $ringCentral,
            );
        } catch (Throwable $exception) {
            report($exception);
            Toast::error('RingCentral check failed: '.$exception->getMessage());
        }
    }

    public function rematchUnmatched(RingCentralCallLogService $ringCentral): void
    {
        try {
            $this->runRingCentralMatch(
                PhoneClick::query()
                    ->notSpam()
                    ->orderByDesc('id')
                    ->limit(100)
                    ->get(),
                force: true,
                emptyMessage: 'No phone clicks to re-check.',
                ringCentral: $ringCentral,
            );
        } catch (Throwable $exception) {
            report($exception);
            Toast::error('Call tracking re-run failed: '.$exception->getMessage());
        }
    }

    public function markAsSpam(Request $request): void
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
        }
    }

    public function restoreFromSpam(Request $request): void
    {
        try {
            $validated = $request->validate([
                'phone_click_id' => ['required', 'integer', 'exists:phone_clicks,id'],
            ]);

            $click = PhoneClick::query()->findOrFail((int) $validated['phone_click_id']);
            $click->restoreFromSpam();
            Toast::info('Phone click restored from spam.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            Toast::error('Could not restore from spam: '.$exception->getMessage());
        }
    }

    public function exportGoogleAdsSheetYesterday(GoogleAdsOfflineSheetExporter $exporter): void
    {
        $this->runGoogleAdsSheetExport($exporter, allPending: false);
    }

    public function exportGoogleAdsSheetAllPending(GoogleAdsOfflineSheetExporter $exporter): void
    {
        $this->runGoogleAdsSheetExport($exporter, allPending: true);
    }

    private function runGoogleAdsSheetExport(GoogleAdsOfflineSheetExporter $exporter, bool $allPending): void
    {
        if (! $exporter->isConfigured()) {
            Toast::error('Google Drive sheet export is not configured (folder, conversion name, service account or OAuth).');

            return;
        }

        try {
            $result = $exporter->export(null, $allPending, false);
        } catch (Throwable $exception) {
            report($exception);
            Toast::error('Google Ads sheet export failed: '.$exception->getMessage());

            return;
        }

        if ($result['count'] === 0) {
            Toast::info($allPending
                ? 'No pending confirmed phone clicks with a GCLID to export.'
                : 'No confirmed phone clicks with a GCLID for yesterday to export.');

            return;
        }

        Toast::success(sprintf(
            'Exported %d conversion(s) to “%s”.',
            $result['count'],
            $result['title'],
        ));

        if (is_string($result['spreadsheet_url']) && $result['spreadsheet_url'] !== '') {
            Toast::info($result['spreadsheet_url']);
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, PhoneClick>  $clicks
     */
    private function runRingCentralMatch(
        $clicks,
        bool $force,
        string $emptyMessage,
        RingCentralCallLogService $ringCentral,
    ): void {
        if ($clicks->isEmpty()) {
            Toast::info($emptyMessage);

            return;
        }

        $found = 0;
        $errors = 0;

        foreach ($clicks as $click) {
            $click->forceFill([
                'ringcentral_checked_at' => null,
            ])->saveQuietly();

            try {
                (new MatchPhoneClickToRingCentral($click->id, $force))->handle($ringCentral);
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
