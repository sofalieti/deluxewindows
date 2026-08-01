<div class="phone-click-actions">
    @if ($click->wasSentToGoogleSheet())
        <span class="badge bg-success text-white">✓ Sent</span>
    @elseif (! empty($sendToGoogle))
        {!! $sendToGoogle !!}
    @endif

    @if (! empty($spamAction))
        {!! $spamAction !!}
    @endif
</div>
