@php
    /** @var \App\Models\Contact $contact */
    /** @var \App\Models\ContactComment|null $note */
    $note = $contact->latestComment;
    $action = rtrim(url()->current(), '/').'/addNote';
@endphp

<div class="lead-note-cell">
    @if ($note)
        <div class="lead-note-cell__last" title="{{ $note->body }}">
            <div class="lead-note-cell__body">{{ \Illuminate\Support\Str::limit(trim((string) $note->body), 90) }}</div>
            <div class="lead-note-cell__meta">
                {{ $note->user?->name ?? 'Unknown' }}
                · {{ optional($note->created_at)->format('m/d H:i') }}
            </div>
        </div>
    @else
        <div class="lead-note-cell__empty">No notes yet</div>
    @endif

    <div class="lead-note-cell__compose">
        <textarea
            class="lead-note-cell__input"
            rows="2"
            maxlength="5000"
            placeholder="Add note…"
            aria-label="Add note for contact {{ $contact->id }}"
        ></textarea>
        <button
            type="button"
            class="lead-note-cell__save"
            data-action-base="{{ $action }}"
            data-contact-id="{{ $contact->id }}"
            onclick="window.contactSubmitNote && window.contactSubmitNote(this)"
        >Save</button>
    </div>
</div>
