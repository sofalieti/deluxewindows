@php
    /** @var \App\Models\Contact $contact */
    $count = (int) ($contact->leads_count ?? $contact->leads->count());
    $url = route('platform.leads', ['filter' => ['contact_id' => $contact->id]]);
    $lastLead = $contact->leads_max_created_at
        ? \Illuminate\Support\Carbon::parse($contact->leads_max_created_at)->format('Y-m-d H:i')
        : null;
@endphp

<div class="contact-leads-cell">
    <a class="badge bg-primary text-white text-decoration-none" href="{{ $url }}" title="Open leads for this contact">
        {{ $count }} {{ $count === 1 ? 'lead' : 'leads' }}
    </a>
    @if ($lastLead)
        <div class="contact-leads-cell__last">Last {{ $lastLead }}</div>
    @endif
</div>
