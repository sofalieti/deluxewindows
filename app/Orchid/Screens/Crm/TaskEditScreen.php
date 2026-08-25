<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Crm;

use App\Models\Contact;
use App\Models\CrmTask;
use App\Models\Lead;
use App\Models\PhoneClick;
use App\Models\User;
use App\Services\CrmTaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
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
        $this->task = $task?->exists ? $task : new CrmTask([
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
            $actions[] = Button::make('Mark done')
                ->icon('bs.check-lg')
                ->type(Color::SUCCESS)
                ->method('complete');
            $actions[] = Button::make('Cancel task')
                ->icon('bs.x-lg')
                ->type(Color::DANGER)
                ->method('cancel')
                ->confirm('Cancel this task?');
        }

        return $actions;
    }

    public function layout(): iterable
    {
        return [
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
                    ->title('Result')
                    ->rows(3)
                    ->canSee((bool) $this->task?->exists),
            ]),
            Layout::view('admin.partials.sticky-save', [
                'label' => 'Save task',
                'method' => 'save',
            ]),
        ];
    }

    public function save(Request $request, CrmTaskService $tasks)
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

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
            'task.result' => ['nullable', 'string', 'max:1000'],
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

    public function complete(CrmTaskService $tasks)
    {
        abort_unless($this->task?->exists, 404);
        $user = Auth::user();
        abort_unless($user instanceof User, 403);
        $this->authorizeTask($this->task);
        $tasks->complete($this->task, $user, $this->task->result);
        Toast::info('Task completed.');

        return redirect()->route('platform.crm.tasks.edit', $this->task);
    }

    public function cancel(CrmTaskService $tasks)
    {
        abort_unless($this->task?->exists, 404);
        $user = Auth::user();
        abort_unless($user instanceof User, 403);
        $this->authorizeTask($this->task);
        $tasks->cancel($this->task, $user);
        Toast::info('Task cancelled.');

        return redirect()->route('platform.crm.tasks.edit', $this->task);
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
