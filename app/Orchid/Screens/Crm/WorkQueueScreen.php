<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Crm;

use App\Models\CrmTask;
use App\Models\Lead;
use App\Models\PhoneClick;
use App\Models\User;
use App\Services\CrmTaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class WorkQueueScreen extends Screen
{
    public function query(): iterable
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $start = now()->startOfDay();
        $end = now()->endOfDay();

        $myTasks = CrmTask::query()
            ->with(['assignee', 'subject', 'contact'])
            ->assignedTo($user)
            ->open()
            ->dueWithin($start, $end)
            ->orderBy('due_at')
            ->paginate(50, pageName: 'today_page');

        $overdue = CrmTask::query()
            ->with(['assignee', 'subject', 'contact'])
            ->assignedTo($user)
            ->overdue()
            ->orderBy('due_at')
            ->paginate(50, pageName: 'overdue_page');

        return [
            'todayTasks' => $myTasks,
            'overdueTasks' => $overdue,
            'unhandledClicks' => PhoneClick::query()
                ->visibleTo($user)
                ->needsHandling()
                ->with(['assignee', 'contact'])
                ->defaultSort('id', 'desc')
                ->paginate(50, pageName: 'clicks_page'),
            'newLeads' => Lead::query()
                ->visibleTo($user)
                ->where('status', Lead::STATUS_NEW)
                ->with('assignee')
                ->defaultSort('id', 'desc')
                ->paginate(50, pageName: 'leads_page'),
        ];
    }

    public function name(): ?string
    {
        return 'My work';
    }

    public function description(): ?string
    {
        return 'Today’s tasks, overdue follow-ups, unhandled phone clicks, and new leads.';
    }

    public function permission(): ?iterable
    {
        return [CrmTask::PERMISSION];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make('All tasks')
                ->icon('bs.list-task')
                ->route('platform.crm.tasks'),
            Link::make('New task')
                ->icon('bs.plus-lg')
                ->route('platform.crm.tasks.create'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::tabs([
                'Due today' => Layout::table('todayTasks', $this->taskColumns()),
                'Overdue' => Layout::table('overdueTasks', $this->taskColumns()),
                'Unhandled clicks' => Layout::table('unhandledClicks', $this->clickColumns()),
                'New leads' => Layout::table('newLeads', $this->leadColumns()),
            ]),
        ];
    }

    /**
     * @return list<TD>
     */
    private function taskColumns(): array
    {
        return [
            TD::make('due_at', 'Due')
                ->render(fn (CrmTask $task) => e(optional($task->due_at)->format('Y-m-d H:i') ?: '—')),
            TD::make('title', 'Task')
                ->render(function (CrmTask $task): string {
                    return '<a class="fw-semibold" href="'.e(route('platform.crm.tasks.edit', $task)).'">'
                        .e($task->title).'</a>'
                        .'<div class="small text-muted">'.e($task->subjectLabel()).'</div>';
                }),
            TD::make('type', 'Type')
                ->render(fn (CrmTask $task) => e($task->typeLabel())),
            TD::make('actions', '')
                ->render(fn (CrmTask $task) => view('admin.crm.task-row-actions', ['task' => $task])),
        ];
    }

    /**
     * @return list<TD>
     */
    private function clickColumns(): array
    {
        return [
            TD::make('created_at', 'Clicked')
                ->render(fn (PhoneClick $click) => e(optional($click->created_at)->format('Y-m-d H:i'))),
            TD::make('phone', 'Phone')
                ->render(function (PhoneClick $click): string {
                    $phone = $click->ringCentralClientPhone() ?: $click->phone;

                    return '<a href="'.e(route('platform.phone-clicks.view', $click)).'">'
                        .e((string) ($phone ?: 'Click #'.$click->id)).'</a>';
                }),
            TD::make('ringcentral_status', 'Call')
                ->render(fn (PhoneClick $click) => e((string) ($click->ringcentral_result ?: $click->ringcentral_status))),
            TD::make('handling_status', 'Handling')
                ->render(fn (PhoneClick $click) => e($click->handlingStatusLabel())),
        ];
    }

    /**
     * @return list<TD>
     */
    private function leadColumns(): array
    {
        return [
            TD::make('created_at', 'Received')
                ->render(fn (Lead $lead) => e(optional($lead->created_at)->format('Y-m-d H:i'))),
            TD::make('full_name', 'Lead')
                ->render(fn (Lead $lead) => '<a href="'.e(route('platform.leads.edit', $lead)).'">'
                    .e($lead->full_name).'</a>'
                    .'<div class="small text-muted">'.e($lead->phone).'</div>'),
            TD::make('assigned_to', 'Assignee')
                ->render(fn (Lead $lead) => e($lead->assignee?->name ?? '—')),
        ];
    }

    public function complete(Request $request, CrmTaskService $tasks): void
    {
        $validated = $request->validate([
            'task' => ['required', 'integer', 'exists:crm_tasks,id'],
        ]);
        $user = Auth::user();
        abort_unless($user instanceof User, 403);
        $task = CrmTask::query()->findOrFail((int) $validated['task']);
        abort_unless((int) $task->assigned_to === (int) $user->id || $user->hasAccess(CrmTask::PERMISSION_ALL), 403);
        $tasks->complete($task, $user);
        Toast::info('Task completed.');
    }

    public function snoozeDay(Request $request, CrmTaskService $tasks): void
    {
        $validated = $request->validate([
            'task' => ['required', 'integer', 'exists:crm_tasks,id'],
        ]);
        $user = Auth::user();
        abort_unless($user instanceof User, 403);
        $task = CrmTask::query()->findOrFail((int) $validated['task']);
        abort_unless((int) $task->assigned_to === (int) $user->id || $user->hasAccess(CrmTask::PERMISSION_ALL), 403);
        $from = $task->due_at?->copy() ?? now();
        $tasks->snooze($task, $from->addDay());
        Toast::info('Task moved +1 day.');
    }
}
