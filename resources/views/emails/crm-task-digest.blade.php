<p>Hi {{ $manager->name }},</p>

<p>Here is your CRM task digest.</p>

<p><a href="{{ $workUrl }}">Open My work</a></p>

@if ($overdue->isNotEmpty())
    <h3>Overdue ({{ $overdue->count() }})</h3>
    <ul>
        @foreach ($overdue as $task)
            <li>
                <strong>{{ $task->title }}</strong>
                — due {{ optional($task->due_at)->format('Y-m-d H:i') }}
                ({{ $task->subjectLabel() }})
            </li>
        @endforeach
    </ul>
@endif

@if ($today->isNotEmpty())
    <h3>Due today ({{ $today->count() }})</h3>
    <ul>
        @foreach ($today as $task)
            <li>
                <strong>{{ $task->title }}</strong>
                — {{ optional($task->due_at)->format('H:i') }}
                ({{ $task->subjectLabel() }})
            </li>
        @endforeach
    </ul>
@endif

<p>Deluxe Windows CRM</p>
