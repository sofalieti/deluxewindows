<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Crm;

use App\Models\Contact;
use App\Models\CrmTask;
use App\Models\Lead;
use App\Models\PhoneClick;
use App\Models\User;
use App\Orchid\Layouts\Crm\CloseTaskModalLayout;
use App\Services\CrmTaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class TaskEditScreen extends Screen
{
    public ?CrmTask $task = null;

    public function query(?CrmTask $task): iterable
    {
        $this->task = $task?->exists ? $task->load(['events.user', 'completedBy']) : new CrmTask([
            'status' => CrmTask::STATUS_OPEN,
            'type' => CrmTask::TYPE_FOLLOWUP,
            'priority' => CrmTask::PRIORITY_NORMAL,
            'assigned_to' => Auth::id(),
            'subject_type' => request('subject_type'),
            'subject_id' => request('subject_id'),
        ]);

        $this->authorizeTask($this->task);

        return [
            'task' => $this->task,
            'events' => $this->task->exists
                ? $this->task->events()->with('user')->latest('id')->get()
                : collect(),
        ];
    }

    public function name(): ?string
    {
        return $this->task?->exists ? 'Task #'.$this->task->id : 'New task';
    }

    public function description(): ?string
    {
        return $this->task?->exists ? $this->task->subjectLabel() : 'Create a follow-up for a manager.';
    }

    public function permission(): ?iterable
    {
        return [CrmTask::PERMISSION];
    }

    public function commandBar(): iterable
    {
        $actions = [
            Link::make('Back to tasks')
                ->icon('bs.arrow-left')
                ->route('platform.crm.tasks'),
        ];

        if ($this->task?->exists && $this->task->isOpen()) {
            $actions[] = ModalToggle::make('Mark done')
                ->icon('bs.check-lg')
                ->type(Color::SUCCESS)
                ->modal('completeTaskModal')
                ->modalTitle('Complete task')
                ->method('complete');
            $actions[] = ModalToggle::make('Cancel task')
                ->icon('bs.x-lg')
                ->type(Color::DANGER)
                ->modal('cancelTaskModal')
                ->modalTitle('Cancel task')
                ->method('cancel');
        }

        if ($this->task?->exists && $this->task->isClosed()) {
            $actions[] = Button::make('Reopen')
                ->icon('bs.arrow-counterclockwise')
                ->type(Color::PRIMARY)
                ->method('reopen')
                ->confirm('Move this task back to Open?');
        }

        return $actions;
    }

    public function layout(): iterable
    {
        $layouts = [
            Layout::rows([
                Input::make('task.title')
                    ->title('Title')
                    ->required()
                    ->maxlength(255),
                Select::make('task.type')
                    ->title('Type')
                    ->options(CrmTask::TYPES)
                    ->required(),
                Select::make('task.priority')
                    ->title('Priority')
                    ->options(CrmTask::PRIORITIES)
                    ->required(),
                Select::make('task.status')
                    ->title('Status')
                    ->options(CrmTask::STATUSES)
                    ->required()
                    ->canSee((bool) $this->task?->exists)
                    ->help('Changing to Done or Cancelled requires a comment in the Result field below.'),
                DateTimer::make('task.due_at')
                    ->title('Due')
                    ->enableTime()
                    ->allowInput(),
                Select::make('task.assigned_to')
                    ->title('Assignee')
                    ->fromModel(User::class, 'name')
                    ->empty('Unassigned'),
                Select::make('task.contact_id')
                    ->title('Contact')
                    ->fromModel(Contact::class, 'full_name')
                    ->empty('None'),
                Select::make('task.subject_type')
                    ->title('Linked record type')
                    ->options([
                        '' => 'None',
                        Lead::class => 'Lead',
                        PhoneClick::class => 'Phone click',
                        Contact::class => 'Contact',
                    ]),
                Input::make('task.subject_id')
                    ->title('Linked record ID')
                    ->type('number'),
                TextArea::make('task.description')
                    ->title('Description')
                    ->rows(5),
                TextArea::make('task.result')
                    ->title('Result / close comment')
                    ->rows(3)
                    ->canSee((bool) $this->task?->exists)
                    ->help('Required when marking the task Done or Cancelled.'),
            ]),
        ];

        if ($this->task?->exists) {
            $layouts[] = Layout::view('admin.crm.task-history');
            $layouts[] = Layout::modal('completeTaskModal', CloseTaskModalLayout::class)
                ->title('Complete task')
                ->applyButton('Complete');
            $layouts[] = Layout::modal('cancelTaskModal', CloseTaskModalLayout::class)
                ->title('Cancel task')
                ->applyButton('Cancel task');
        }

        $layouts[] = Layout::view('admin.partials.sticky-save', [
            'label' => 'Save task',
            'method' => 'save',
        ]);

        return $layouts;
    }

    public function save(Request $request, CrmTaskService $tasks)
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $closing = $this->task?->exists
            && $this->task->isOpen()
            && in_array((string) $request->input('task.status'), [
                CrmTask::STATUS_DONE,
                CrmTask::STATUS_CANCELLED,
            ], true);

        $validated = $request->validate([
            'task.title' => ['required', 'string', 'max:255'],
            'task.type' => ['required', 'string', Rule::in(array_keys(CrmTask::TYPES))],
            'task.priority' => ['required', 'string', Rule::in(array_keys(CrmTask::PRIORITIES))],
            'task.due_at' => ['nullable', 'date'],
            'task.assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'task.contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'task.subject_type' => ['nullable', 'string', Rule::in(['', Lead::class, PhoneClick::class, Contact::class])],
            'task.subject_id' => ['nullable', 'integer'],
            'task.description' => ['nullable', 'string', 'max:5000'],
            'task.result' => [$closing ? 'required' : 'nullable', 'string', 'max:1000'],
            'task.status' => ['nullable', 'string', Rule::in(array_keys(CrmTask::STATUSES))],
        ], [
            'task.result.required' => 'A comment is required when closing a task.',
        ]);

        $payload = $validated['task'];
        $subjectType = trim((string) ($payload['subject_type'] ?? ''));
        $subjectId = isset($payload['subject_id']) ? (int) $payload['subject_id'] : 0;
        $subject = $this->resolveSubject($subjectType, $subjectId);

        if ($this->task?->exists) {
            $this->authorizeTask($this->task);
            $this->task->fill([
                'title' => $payload['title'],
                'type' => $payload['type'],
                'priority' => $payload['priority'],
                'due_at' => $payload['due_at'] ?? null,
                'assigned_to' => $payload['assigned_to'] ?? null,
                'contact_id' => $payload['contact_id'] ?? $subject?->contact_id ?? ($subject instanceof Contact ? $subject->id : null),
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'description' => $payload['description'] ?? null,
                'result' => $payload['result'] ?? $this->task->result,
            ])->save();

            $this->applyStatusChange($tasks, $user, (string) ($payload['status'] ?? $this->task->status));
            Toast::info('Task saved.');

            return redirect()->route('platform.crm.tasks.edit', $this->task);
        }

        $task = $tasks->create([
            'title' => $payload['title'],
            'type' => $payload['type'],
            'priority' => $payload['priority'],
            'due_at' => $payload['due_at'] ?? null,
            'assigned_to' => $payload['assigned_to'] ?? $user->id,
            'created_by' => $user->id,
            'contact_id' => $payload['contact_id'] ?? null,
            'subject' => $subject,
            'description' => $payload['description'] ?? null,
        ]);
        Toast::info('Task created.');

        return redirect()->route('platform.crm.tasks.edit', $task);
    }

    public function complete(Request $request, CrmTaskService $tasks)
    {
        abort_unless($this->task?->exists, 404);
        $user = Auth::user();
        abort_unless($user instanceof User, 403);
        $this->authorizeTask($this->task);

        $validated = $request->validate([
            'result' => ['required', 'string', 'min:1', 'max:1000'],
        ], [
            'result.required' => 'A comment is required when closing a task.',
        ]);

        $tasks->complete($this->task, $user, $validated['result']);
        Toast::info('Task completed.');

        return redirect()->route('platform.crm.tasks.edit', $this->task);
    }

    public function reopen(CrmTaskService $tasks)
    {
        abort_unless($this->task?->exists, 404);
        $user = Auth::user();
        abort_unless($user instanceof User, 403);
        $this->authorizeTask($this->task);
        $tasks->reopen($this->task, $user);
        Toast::info('Task reopened.');

        return redirect()->route('platform.crm.tasks.edit', $this->task);
    }

    public function cancel(Request $request, CrmTaskService $tasks)
    {
        abort_unless($this->task?->exists, 404);
        $user = Auth::user();
        abort_unless($user instanceof User, 403);
        $this->authorizeTask($this->task);

        $validated = $request->validate([
            'result' => ['required', 'string', 'min:1', 'max:1000'],
        ], [
            'result.required' => 'A comment is required when closing a task.',
        ]);

        $tasks->cancel($this->task, $user, $validated['result']);
        Toast::info('Task cancelled.');

        return redirect()->route('platform.crm.tasks.edit', $this->task);
    }

    private function applyStatusChange(CrmTaskService $tasks, User $user, string $status): void
    {
        $task = $this->task?->refresh();
        if ($task === null || $task->status === $status) {
            return;
        }

        if ($status === CrmTask::STATUS_OPEN) {
            $tasks->reopen($task, $user);

            return;
        }

        $comment = trim((string) $task->result);
        if ($comment === '') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'task.result' => 'A comment is required when closing a task.',
            ]);
        }

        if ($status === CrmTask::STATUS_DONE) {
            $tasks->complete($task, $user, $comment);

            return;
        }

        if ($status === CrmTask::STATUS_CANCELLED) {
            $tasks->cancel($task, $user, $comment);
        }
    }

    private function authorizeTask(CrmTask $task): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);
        if (! $task->exists || $user->hasAccess(CrmTask::PERMISSION_ALL)) {
            return;
        }

        abort_unless((int) $task->assigned_to === (int) $user->id, 403);
    }

    private function resolveSubject(string $type, int $id): Lead|PhoneClick|Contact|null
    {
        if ($type === '' || $id < 1) {
            return null;
        }

        return match ($type) {
            Lead::class => Lead::query()->find($id),
            PhoneClick::class => PhoneClick::query()->find($id),
            Contact::class => Contact::query()->find($id),
            default => null,
        };
    }
}
