@php
    /** @var array{with_transcript: int, quote_discussed: int, quote_rate: float, appointment: int, appointment_rate: float, objections: list<array{text: string, count: int}>} $transcripts */
    $transcripts = $transcripts ?? [];
@endphp

<div class="row g-3">
    <div class="col-md-4">
        <div class="small text-muted">Calls with transcript</div>
        <div class="fs-4 fw-semibold">{{ number_format((int) ($transcripts['with_transcript'] ?? 0)) }}</div>
    </div>
    <div class="col-md-4">
        <div class="small text-muted">Discussed price</div>
        <div class="fs-4 fw-semibold">{{ number_format((float) ($transcripts['quote_rate'] ?? 0), 1) }}%</div>
        <div class="small text-muted">{{ (int) ($transcripts['quote_discussed'] ?? 0) }} calls</div>
    </div>
    <div class="col-md-4">
        <div class="small text-muted">Appointment mentioned</div>
        <div class="fs-4 fw-semibold">{{ number_format((float) ($transcripts['appointment_rate'] ?? 0), 1) }}%</div>
        <div class="small text-muted">{{ (int) ($transcripts['appointment'] ?? 0) }} calls</div>
    </div>
</div>

@if (($transcripts['objections'] ?? []) !== [])
    <hr>
    <h6 class="mb-2">Top objections</h6>
    <ul class="mb-0 ps-3">
        @foreach ($transcripts['objections'] as $objection)
            <li>
                {{ $objection['text'] }}
                <span class="text-muted">({{ $objection['count'] }})</span>
            </li>
        @endforeach
    </ul>
@else
    <p class="text-muted mt-3 mb-0">No objection themes in completed transcripts for this period.</p>
@endif
