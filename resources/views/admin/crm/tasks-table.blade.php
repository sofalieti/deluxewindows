@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\CrmTask>|\App\Models\CrmTask[] $tasks */
    $tasks = $tasks ?? collect();
@endphp

<div class="bg-white rounded shadow-sm p-4 contact-panel">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
        <h6 class="text-muted text-uppercase mb-0 contact-panel__title">Tasks</h6>
        <span class="badge bg-primary text-white">{{ $tasks instanceof \Illuminate\Support\Collection ? $tasks->count() : count($tasks) }}</span>
    </div>

    @forelse ($tasks as $task)
        <div class="contact-lead {{ $loop->last ? '' : 'border-bottom' }}">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <a class="fw-semibold" href="{{ route('platform.crm.tasks.edit', $task) }}">
                        {{ $task->title }}
                    </a>
                    <span class="lead-status-badge lead-status-badge--{{ $task->isOverdue() ? 'lost' : ($task->isOpen() ? 'new' : 'sold') }}">
                        {{ $task->statusLabel() }}
                    </span>
                    <div class="small text-muted mt-1">
                        {{ $task->typeLabel() }}
                        @if ($task->due_at)
                            · due {{ $task->due_at->format('Y-m-d H:i') }}
                        @endif
                        @if ($task->assignee)
                            · {{ $task->assignee->name }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <p class="text-muted mb-0">No tasks yet.</p>
    @endforelse
</div>
