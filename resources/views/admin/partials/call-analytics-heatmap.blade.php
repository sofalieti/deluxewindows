@php
    /** @var array{headers: list<string>, rows: list<array{day: string, cells: list<array{hour: int, count: int, intensity: float}>}>, max?: int} $heatmap */
    $heatmap = $heatmap ?? ['headers' => [], 'rows' => [], 'max' => 0];
@endphp

@if (($heatmap['rows'] ?? []) === [])
    <p class="text-muted mb-0">No missed or voicemail calls in this period.</p>
@else
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0 call-analytics-heatmap">
            <thead>
                <tr>
                    <th scope="col">Day</th>
                    @foreach ($heatmap['headers'] as $header)
                        <th scope="col" class="text-center small">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($heatmap['rows'] as $row)
                    <tr>
                        <th scope="row">{{ $row['day'] }}</th>
                        @foreach ($row['cells'] as $cell)
                            @php
                                $level = (int) round(((float) ($cell['intensity'] ?? 0)) * 10);
                                $level = max(0, min(10, $level));
                            @endphp
                            <td
                                class="call-analytics-heatmap__cell call-analytics-heatmap__cell--{{ $level }}"
                                title="{{ $row['day'] }} {{ sprintf('%02d:00', $cell['hour']) }} — {{ $cell['count'] }}"
                            >
                                {{ $cell['count'] > 0 ? $cell['count'] : '' }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
