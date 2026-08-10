@php
    /** @var array{uploaded: int, waiting: int, failed: int, last_sent_label: string, last_error: ?string, last_error_click_id: ?int} $bing */
    /** @var array{uploaded: int, waiting: int, failed: int, last_sent_label: string, last_error: ?string, last_error_click_id: ?int} $google */
    $bing = $conversionStats['bing'] ?? [];
    $google = $conversionStats['google'] ?? [];
@endphp

<div class="offline-conversion-stats">
    <div class="row g-3">
        <div class="col-md-6">
            <div class="offline-conversion-stats__card">
                <div class="offline-conversion-stats__title">Microsoft Ads (Bing)</div>
                <div class="offline-conversion-stats__last">
                    Last sent: <strong>{{ $bing['last_sent_label'] ?? 'never' }}</strong>
                </div>
                <div class="offline-conversion-stats__counts">
                    <span>Uploaded {{ number_format((int) ($bing['uploaded'] ?? 0)) }}</span>
                    <span>Waiting {{ number_format((int) ($bing['waiting'] ?? 0)) }}</span>
                    <span class="{{ ((int) ($bing['failed'] ?? 0)) > 0 ? 'text-danger' : '' }}">
                        Failed {{ number_format((int) ($bing['failed'] ?? 0)) }}
                    </span>
                </div>
                @if (! empty($bing['last_error']))
                    <div class="offline-conversion-stats__error">
                        Last error
                        @if (! empty($bing['last_error_click_id']))
                            (<a href="{{ route('platform.phone-clicks.view', ['click' => $bing['last_error_click_id']]) }}">#{{ $bing['last_error_click_id'] }}</a>):
                        @endif
                        {{ $bing['last_error'] }}
                    </div>
                @endif
            </div>
        </div>
        <div class="col-md-6">
            <div class="offline-conversion-stats__card">
                <div class="offline-conversion-stats__title">Google Ads</div>
                <div class="offline-conversion-stats__last">
                    Last sent: <strong>{{ $google['last_sent_label'] ?? 'never' }}</strong>
                </div>
                <div class="offline-conversion-stats__counts">
                    <span>Uploaded {{ number_format((int) ($google['uploaded'] ?? 0)) }}</span>
                    <span>Waiting {{ number_format((int) ($google['waiting'] ?? 0)) }}</span>
                    <span class="{{ ((int) ($google['failed'] ?? 0)) > 0 ? 'text-danger' : '' }}">
                        Failed {{ number_format((int) ($google['failed'] ?? 0)) }}
                    </span>
                </div>
                @if (! empty($google['last_error']))
                    <div class="offline-conversion-stats__error">
                        Last error
                        @if (! empty($google['last_error_click_id']))
                            (<a href="{{ route('platform.phone-clicks.view', ['click' => $google['last_error_click_id']]) }}">#{{ $google['last_error_click_id'] }}</a>):
                        @endif
                        {{ $google['last_error'] }}
                    </div>
                @endif
            </div>
        </div>
    </div>
    <p class="small text-muted mb-0 mt-2">
        Confirmed RingCentral calls with a click id (msclkid / gclid), last {{ \App\Services\Ads\OfflineConversionStatsService::WINDOW_DAYS }} days.
    </p>
</div>
