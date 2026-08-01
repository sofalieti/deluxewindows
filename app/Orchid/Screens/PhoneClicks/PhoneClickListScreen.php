<?php

declare(strict_types=1);

namespace App\Orchid\Screens\PhoneClicks;

use App\Jobs\MatchPhoneClickToRingCentral;
use App\Models\PhoneClick;
use App\Services\PhoneClickGoogleBridge;
use App\Services\RingCentralCallLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
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
        $base = PhoneClick::query()->defaultSort('id', 'desc');
        $hasSpam = Schema::hasColumn('phone_clicks', 'is_spam');

        return [
            'clicks' => (clone $base)
                ->when($hasSpam, fn ($q) => $q->where('is_spam', false))
                ->paginate(50, pageName: 'page'),
            'spamClicks' => (clone $base)
                ->when($hasSpam, fn ($q) => $q->where('is_spam', true), fn ($q) => $q->whereRaw('0 = 1'))
                ->paginate(50, pageName: 'spam_page'),
        ];
    }

    public function name(): ?string
    {
        return 'Phone clicks';
    }

    public function description(): ?string
    {
        return 'Website phone clicks with traffic source, RingCentral call tracking, and spam tab.';
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
                ->confirm('Re-check pending / error phone clicks against the primary site number in RingCentral?'),
            Button::make('Re-run call tracking')
                ->icon('bs.link-45deg')
                ->method('rematchUnmatched')
                ->confirm('Reset and re-match the latest 100 non-spam phone clicks against the primary site number only?'),
        ];
    }

    public function layout(): iterable
    {
        return [
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
            TD::make('created_at', 'Click time')
                ->sort()
                ->width('130px')
                ->render(function (PhoneClick $click): string {
                    if (! $click->created_at) {
                        return '-';
                    }

                    $clickAt = $click->created_at->copy()->timezone('America/Los_Angeles');

                    return '<div class="fw-semibold">'.e($clickAt->format('M d, Y')).'</div>'
                        .'<div class="small text-muted">'.e($clickAt->format('h:i A')).' PT</div>';
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

            TD::make('utm_source', 'Traffic')
                ->width('180px')
                ->render(function (PhoneClick $click): string {
                    $lastDetail = $click->trafficSourceDetail();
                    $firstDetail = $click->firstTrafficSourceDetail();

                    $html = '<div class="small text-muted">Last</div>'
                        .'<span class="badge bg-'.$click->trafficSourceColor().' text-white">'
                        .e($click->trafficSourceLabel())
                        .'</span>';
                    if ($lastDetail !== '') {
                        $html .= '<div class="small text-muted">'.e(Str::limit($lastDetail, 24)).'</div>';
                    }

                    $html .= '<div class="small text-muted mt-2">First</div>'
                        .'<span class="badge bg-'.$click->firstTrafficSourceColor().' text-white">'
                        .e($click->firstTrafficSourceLabel())
                        .'</span>';
                    if ($firstDetail !== '') {
                        $html .= '<div class="small text-muted">'.e(Str::limit($firstDetail, 24)).'</div>';
                    }

                    return $html;
                }),

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

                        return '<span class="badge bg-success text-white">✓ '.e($result).'</span>'
                            .'<div class="small text-muted mt-1">'
                            .($callAt ? 'Call '.e($callAt).' PT · ' : '')
                            .e($click->ringCentralDurationLabel())
                            .' · '.e((string) ($click->ringcentral_from_phone ?: 'Unknown caller'))
                            .e($lagLabel)
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

            TD::make('id', 'View')
                ->align(TD::ALIGN_CENTER)
                ->width('70px')
                ->render(fn (PhoneClick $click) => Link::make('')
                    ->icon('bs.eye')
                    ->route('platform.phone-clicks.view', $click)),
        ];

        $columns[] = $spamTab
            ? TD::make('restore', '')
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn (PhoneClick $click) => Button::make('Restore')
                    ->icon('bs.arrow-counterclockwise')
                    ->method('restoreFromSpam', ['click' => $click->id])
                    ->confirm('Move this phone click back to the main list?'))
            : TD::make('spam', '')
                ->align(TD::ALIGN_CENTER)
                ->width('90px')
                ->render(fn (PhoneClick $click) => Button::make('Spam')
                    ->icon('bs.shield-exclamation')
                    ->type(Color::DANGER)
                    ->method('markAsSpam', ['click' => $click->id])
                    ->confirm('Mark this phone click as spam and hide it from the main list?'));

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
        $this->runRingCentralMatch(
            PhoneClick::query()
                ->where('is_spam', false)
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
    }

    public function rematchUnmatched(RingCentralCallLogService $ringCentral): void
    {
        $this->runRingCentralMatch(
            PhoneClick::query()
                ->where('is_spam', false)
                ->orderByDesc('id')
                ->limit(100)
                ->get(),
            force: true,
            emptyMessage: 'No phone clicks to re-check.',
            ringCentral: $ringCentral,
        );
    }

    public function markAsSpam(Request $request): void
    {
        $validated = $request->validate([
            'click' => ['required', 'integer', 'exists:phone_clicks,id'],
        ]);

        $click = PhoneClick::query()->findOrFail((int) $validated['click']);
        $click->markAsSpam();
        Toast::info('Phone click marked as spam.');
    }

    public function restoreFromSpam(Request $request): void
    {
        $validated = $request->validate([
            'click' => ['required', 'integer', 'exists:phone_clicks,id'],
        ]);

        $click = PhoneClick::query()->findOrFail((int) $validated['click']);
        $click->restoreFromSpam();
        Toast::info('Phone click restored from spam.');
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
