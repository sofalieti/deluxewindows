@php
    /** @var array{inbound: int, outbound: int, connected: bool, connected_count: int} $stats */
    $stats = $stats ?? [
        'inbound' => 0,
        'outbound' => 0,
        'connected' => false,
        'connected_count' => 0,
    ];
    $inbound = (int) ($stats['inbound'] ?? 0);
    $outbound = (int) ($stats['outbound'] ?? 0);
    $connected = (bool) ($stats['connected'] ?? false);
@endphp
<div class="call-stats-cell">
    <div class="call-stats-cell__counts">
        <span class="call-stats-cell__in" title="Inbound">↓ {{ $inbound }}</span>
        <span class="call-stats-cell__out" title="Outbound">↑ {{ $outbound }}</span>
    </div>
    @if ($inbound === 0 && $outbound === 0)
        <div class="call-stats-cell__status call-stats-cell__status--none">No calls</div>
    @elseif ($connected)
        <div class="call-stats-cell__status call-stats-cell__status--ok">✓ Connected</div>
    @else
        <div class="call-stats-cell__status call-stats-cell__status--miss">No connect</div>
    @endif
</div>
