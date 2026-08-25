<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Crm;

use App\Models\CrmTask;
use App\Models\User;
use App\Services\CrmTaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class TaskListScreen extends Screen
{
    public function query(): iterable
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $base = CrmTask::query()->with(['assignee', 'contact', 'subject']);
        if (! $user->hasAccess(CrmTask::PERMISSION_ALL)) {
            $base->assignedTo($user);
        }

        return [
            'openTasks' => (clone $base)->open()->defaultSort('due_at', 'asc')->paginate(50, pageName: 'page'),
            'overdueTasks' => (clone $base)->overdue()->defaultSort('due_at', 'asc')->paginate(50, pageName: 'overdue_page'),
            'doneTasks' => (clone $base)->where('status', CrmTask::STATUS_DONE)
                ->defaultSort('completed_at', 'desc')
                ->paginate(50, pageName: 'done_page'),
        ];
    }

    public function name(): ?string
    {
        return 'Tasks';
    }

    public function description(): ?string
    {
        return 'Manager follow-ups for leads, phone clicks, and contacts.';
    }

    public function permission(): ?iterable
    {
        return [CrmTask::PERMISSION];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make('New task')
                ->icon('bs.plus-lg')
                ->route('platform.crm.tasks.create'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::tabs([
                'Open' => Layout::table('openTasks', $this->columns()),
                'Overdue' => Layout::table('overdueTasks', $this->columns()),
                'Done' => Layout::table('doneTasks', $this->columns()),
            ]),
        ];
    }

    /**
     * @return list<TD>
     */
    private function columns(): array
    {
        return [
            TD::make('due_at', 'Due')
                ->sort()
                ->render(fn (CrmTask $task) => e(optional($task->due_at)->format('Y-m-d H:i') ?: '—')),
            TD::make('title', 'Task')
                ->render(function (CrmTask $task): string {
                    $html = '<a class="fw-semibold" href="'.e(route('platform.crm.tasks.edit', $task)).'">'
                        .e($task->title).'</a>';
                    $html .= '<div class="small text-muted">'.e($task->subjectLabel()).'</div>';

                    return $html;
                }),
            TD::make('type', 'Type')
                ->render(fn (CrmTask $task) => e($task->typeLabel())),
            TD::make('status', 'Status')
                ->render(fn (CrmTask $task) => e($task->statusLabel()).($task->isOverdue() ? ' · overdue' : '')),
            TD::make('assigned_to', 'Assignee')
                ->render(fn (CrmTask $task) => e($task->assignee?->name ?? '—')),
            TD::make('actions', '')
                ->render(fn (CrmTask $task) => view('admin.crm.task-row-actions', ['task' => $task])),
        ];
    }

    public function complete(Request $request, CrmTaskService $tasks): void
    {
        $task = $this->findAuthorizedTask($request);
        $user = Auth::user();
        abort_unless($user instanceof User, 403);
        $tasks->complete($task, $user);
        Toast::info('Task completed.');
    }

    public function reopen(Request $request, CrmTaskService $tasks): void
    {
        $task = $this->findAuthorizedTask($request);
        $user = Auth::user();
        abort_unless($user instanceof User, 403);
        $tasks->reopen($task, $user);
        Toast::info('Task reopened.');
    }

    public function snoozeDay(Request $request, CrmTaskService $tasks): void
    {
        $task = $this->findAuthorizedTask($request);
        $from = $task->due_at?->copy() ?? now();
        $tasks->snooze($task, $from->addDay());
        Toast::info('Task moved +1 day.');
    }

    private function findAuthorizedTask(Request $request): CrmTask
    {
        $validated = $request->validate([
            'task' => ['required', 'integer', 'exists:crm_tasks,id'],
        ]);
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $task = CrmTask::query()->findOrFail((int) $validated['task']);
        abort_unless(
            $user->hasAccess(CrmTask::PERMISSION_ALL) || (int) $task->assigned_to === (int) $user->id,
            403
        );

        return $task;
    }
}
