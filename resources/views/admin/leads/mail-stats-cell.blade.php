@php
    /** @var array{inbound: int, outbound: int, last_direction: ?string} $stats */
    $stats = $stats ?? ['inbound' => 0, 'outbound' => 0, 'last_direction' => null];
    $inbound = (int) ($stats['inbound'] ?? 0);
    $outbound = (int) ($stats['outbound'] ?? 0);
    $lastInbound = ($stats['last_direction'] ?? null) === \App\Models\MailboxMessage::DIRECTION_INBOUND;
@endphp

<div class="mail-stats-cell {{ $lastInbound ? 'mail-stats-cell--needs-reply' : '' }}"
     @if ($lastInbound) title="Last email is inbound — reply needed" @endif>
    <div class="mail-stats-cell__counts">
        <span class="mail-stats-cell__in" title="Inbound">↓ {{ $inbound }}</span>
        <span class="mail-stats-cell__out" title="Outbound">↑ {{ $outbound }}</span>
    </div>
    @if ($inbound === 0 && $outbound === 0)
        <div class="mail-stats-cell__empty">no mail</div>
    @elseif ($lastInbound)
        <div class="mail-stats-cell__flag">IN last</div>
    @endif
</div>
