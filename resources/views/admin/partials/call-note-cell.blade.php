@php
    /** @var \App\Models\CrmNote|null $note */
    $note = $subject->latestNote;
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
            aria-label="Add note"
        ></textarea>
        <button
            type="button"
            class="lead-note-cell__save"
            data-action-base="{{ $action }}"
            data-subject-id="{{ $subject->id }}"
            onclick="window.callSubmitNote && window.callSubmitNote(this)"
        >Save</button>
    </div>
</div>
