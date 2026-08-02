@include('admin.partials.ringcentral-calls-list', [
    'phone' => trim((string) ($contact->phone ?? '')),
    'calls' => $calls ?? collect(),
    'emptyHint' => 'No RingCentral calls found for this phone number yet.',
])
