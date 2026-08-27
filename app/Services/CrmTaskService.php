<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use App\Models\CrmNote;
use App\Models\CrmTask;
use App\Models\CrmTaskEvent;
use App\Models\Lead;
use App\Models\LeadChange;
use App\Models\PhoneClick;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

final class CrmTaskService
{
    /**
     * @param  array{
     *     title: string,
     *     description?: ?string,
     *     type?: string,
     *     priority?: string,
     *     due_at?: \DateTimeInterface|string|null,
     *     assigned_to?: ?int,
     *     created_by?: ?int,
     *     contact_id?: ?int,
     *     subject?: ?Model,
     *     subject_type?: ?string,
     *     subject_id?: ?int
     * }  $data
     */
    public function create(array $data): CrmTask
    {
        $subject = $data['subject'] ?? null;
        $subjectType = $subject?->getMorphClass() ?? ($data['subject_type'] ?? null);
        $subjectId = $subject?->getKey() ?? ($data['subject_id'] ?? null);

        $contactId = $data['contact_id'] ?? null;
        if ($contactId === null && $subject instanceof Lead) {
            $contactId = $subject->contact_id;
        }
        if ($contactId === null && $subject instanceof PhoneClick) {
            $contactId = $subject->contact_id;
        }
        if ($contactId === null && $subject instanceof Contact) {
            $contactId = $subject->id;
        }

        $task = CrmTask::query()->create([
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'contact_id' => $contactId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? CrmTask::TYPE_OTHER,
            'status' => CrmTask::STATUS_OPEN,
            'priority' => $data['priority'] ?? CrmTask::PRIORITY_NORMAL,
            'due_at' => $data['due_at'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null,
            'created_by' => $data['created_by'] ?? Auth::id(),
        ]);

        $this->audit($task, 'Created task: '.$task->title);

        return $task;
    }

    public function complete(CrmTask $task, User $user, string $result): CrmTask
    {
        if ($task->status === CrmTask::STATUS_DONE) {
            return $task;
        }

        $comment = $this->requireComment($result);

        $task->forceFill([
            'status' => CrmTask::STATUS_DONE,
            'completed_at' => now(),
            'completed_by' => $user->id,
            'result' => $comment,
        ])->save();

        $this->recordEvent($task, CrmTaskEvent::ACTION_COMPLETED, $user->id, $comment);
        $this->audit($task, 'Completed task: '.$task->title.' — '.$comment);

        return $task->refresh();
    }

    public function reopen(CrmTask $task, User $user, ?string $comment = null): CrmTask
    {
        if ($task->status === CrmTask::STATUS_OPEN) {
            return $task;
        }

        $from = $task->statusLabel();
        $note = $comment !== null && trim($comment) !== '' ? trim($comment) : null;

        $task->forceFill([
            'status' => CrmTask::STATUS_OPEN,
            'completed_at' => null,
            'completed_by' => null,
        ])->save();

        $this->recordEvent($task, CrmTaskEvent::ACTION_REOPENED, $user->id, $note);
        $this->audit($task, 'Reopened task: '.$task->title.' (was '.$from.') by #'.$user->id);

        return $task->refresh();
    }

    public function cancel(CrmTask $task, User $user, string $result): CrmTask
    {
        if ($task->status === CrmTask::STATUS_CANCELLED) {
            return $task;
        }

        $comment = $this->requireComment($result);

        $task->forceFill([
            'status' => CrmTask::STATUS_CANCELLED,
            'completed_at' => now(),
            'completed_by' => $user->id,
            'result' => $comment,
        ])->save();

        $this->recordEvent($task, CrmTaskEvent::ACTION_CANCELLED, $user->id, $comment);
        $this->audit($task, 'Cancelled task: '.$task->title.' — '.$comment);

        return $task->refresh();
    }

    public function reassign(CrmTask $task, ?User $assignee): CrmTask
    {
        $from = $task->assigned_to;
        $to = $assignee?->id;
        if ((int) $from === (int) $to) {
            return $task;
        }

        $task->assigned_to = $to;
        $task->save();

        $this->audit(
            $task,
            'Reassigned task from '.($from ? '#'.$from : 'unassigned').' to '.($to ? '#'.$to : 'unassigned')
        );

        return $task;
    }

    public function snooze(CrmTask $task, \DateTimeInterface $dueAt): CrmTask
    {
        $task->due_at = $dueAt;
        $task->save();

        $this->audit($task, 'Snoozed task until '.$task->due_at?->format('Y-m-d H:i'));

        return $task;
    }

    public function closeOpenFor(Model $subject, ?string $type = null, ?string $result = null): int
    {
        $query = CrmTask::query()
            ->open()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey());

        if ($type !== null) {
            $query->where('type', $type);
        }

        $count = 0;
        $comment = trim((string) ($result ?? 'Closed automatically')) ?: 'Closed automatically';
        $userId = Auth::id();

        $query->get()->each(function (CrmTask $task) use ($comment, $userId, &$count): void {
            $task->forceFill([
                'status' => CrmTask::STATUS_DONE,
                'completed_at' => now(),
                'completed_by' => $userId,
                'result' => $comment,
            ])->save();
            $this->recordEvent($task, CrmTaskEvent::ACTION_AUTO_COMPLETED, $userId, $comment);
            $this->audit($task, 'Auto-closed task: '.$task->title);
            $count++;
        });

        return $count;
    }

    public function findOpenOfType(Model $subject, string $type): ?CrmTask
    {
        return CrmTask::query()
            ->open()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->where('type', $type)
            ->latest('id')
            ->first();
    }

    private function requireComment(string $result): string
    {
        $comment = trim($result);
        if ($comment === '') {
            throw new InvalidArgumentException('A comment is required when closing a task.');
        }

        return $comment;
    }

    private function recordEvent(CrmTask $task, string $action, ?int $userId, ?string $comment): void
    {
        CrmTaskEvent::query()->create([
            'crm_task_id' => $task->id,
            'user_id' => $userId,
            'action' => $action,
            'comment' => $comment,
            'created_at' => now(),
        ]);
    }

    private function audit(CrmTask $task, string $summary): void
    {
        $subject = $task->subject;
        $userId = Auth::id();

        if ($subject instanceof Lead) {
            LeadChange::record($subject, 'crm_task', null, (string) $task->id, $summary, $userId);

            return;
        }

        if ($subject instanceof PhoneClick && $userId) {
            CrmNote::query()->create([
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->id,
                'user_id' => $userId,
                'body' => $summary,
            ]);
        }
    }
}
