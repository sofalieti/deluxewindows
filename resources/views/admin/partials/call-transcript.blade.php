@php
    /** @var \App\Models\RingCentralCall|null $call */
    $call = $call ?? null;
    $compact = (bool) ($compact ?? false);
    $canQueue = (bool) ($canQueue ?? false);
@endphp

@if ($call)
    @php
        $status = (string) ($call->transcript_status ?? '');
        $summary = is_array($call->transcript_summary) ? $call->transcript_summary : [];
        $overview = trim((string) ($summary['overview'] ?? ''));
        $agreements = array_values(array_filter((array) ($summary['agreements'] ?? [])));
        $nextSteps = array_values(array_filter((array) ($summary['next_steps'] ?? [])));
        $objections = array_values(array_filter((array) ($summary['objections'] ?? [])));
        $appointment = trim((string) ($summary['appointment'] ?? ''));
        $quote = trim((string) ($summary['quote_discussed'] ?? ''));
        $transcript = trim((string) ($call->transcript ?? ''));
    @endphp

    <div class="call-transcript {{ $compact ? 'call-transcript--compact' : '' }}">
        @if ($status === 'completed')
            <div class="call-transcript__badge call-transcript__badge--ok">Transcript</div>
            @if ($overview !== '')
                <div class="call-transcript__overview">{{ $overview }}</div>
            @endif

            @unless ($compact)
                @if ($agreements !== [])
                    <div class="call-transcript__block">
                        <div class="call-transcript__label">Agreed</div>
                        <ul class="call-transcript__list">
                            @foreach ($agreements as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if ($nextSteps !== [])
                    <div class="call-transcript__block">
                        <div class="call-transcript__label">Next steps</div>
                        <ul class="call-transcript__list">
                            @foreach ($nextSteps as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if ($objections !== [])
                    <div class="call-transcript__block">
                        <div class="call-transcript__label">Objections</div>
                        <ul class="call-transcript__list">
                            @foreach ($objections as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if ($appointment !== '')
                    <div class="call-transcript__meta">Appointment: {{ $appointment }}</div>
                @endif
                @if ($quote !== '')
                    <div class="call-transcript__meta">Quote: {{ $quote }}</div>
                @endif
                @if ($transcript !== '')
                    <details class="call-transcript__full">
                        <summary>Full transcript</summary>
                        <pre class="call-transcript__text">{{ $transcript }}</pre>
                    </details>
                @endif
            @endunless
        @elseif ($status === 'pending')
            <div class="call-transcript__badge call-transcript__badge--pending">Queued for transcript</div>
        @elseif ($status === 'processing')
            <div class="call-transcript__badge call-transcript__badge--pending">Transcribing…</div>
        @elseif ($status === 'failed')
            <div class="call-transcript__badge call-transcript__badge--fail">Transcript failed</div>
            @unless ($compact)
                @if (filled($call->transcript_error))
                    <div class="call-transcript__error">{{ $call->transcript_error }}</div>
                @endif
            @endunless
        @elseif ($status === 'skipped')
            <div class="call-transcript__badge">Transcript skipped</div>
        @elseif ($call->hasRecording())
            <div class="call-transcript__badge">No transcript yet</div>
        @endif

        @if ($canQueue && $call->hasRecording())
            <div class="call-transcript__actions">
                {!! \Orchid\Screen\Actions\Button::make($status === 'completed' || $status === 'failed' ? 'Re-transcribe' : 'Transcribe')
                    ->icon('bs.soundwave')
                    ->method('queueTranscript', ['call' => $call->id])
                    ->confirm($status === 'completed'
                        ? 'Re-run OpenAI transcription and summary for this call?'
                        : 'Queue this call for OpenAI transcription and summary?')
                    ->render() !!}
            </div>
        @endif
    </div>
@endif
