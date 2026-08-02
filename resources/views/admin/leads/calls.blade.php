@include('admin.partials.ringcentral-calls-list', [
    'phone' => trim((string) ($lead->phone ?? '')),
    'calls' => $calls ?? collect(),
    'emptyHint' => 'No RingCentral calls found for this lead phone yet.',
])
