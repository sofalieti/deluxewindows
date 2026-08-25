<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use App\Models\CrmNote;
use App\Models\CrmTask;
use App\Models\LeadChange;
use App\Models\LeadComment;
use App\Models\PhoneClick;
use Illuminate\Support\Collection;

final class CrmTimelineService
{
    /**
     * @return Collection<int, object{
     *     kind: string,
     *     title: string,
     *     body: string,
     *     created_at: \Illuminate\Support\Carbon|null,
     *     user_name: string,
     *     url: ?string
     * }>
     */
    public function forContact(Contact $contact): Collection
    {
        $items = collect();

        $contact->loadMissing([
            'comments.user',
            'changes.user',
            'leads.comments.user',
            'leads.changes.user',
            'phoneClicks.notes.user',
            'tasks.assignee',
        ]);

        foreach ($contact->comments as $comment) {
            $items->push((object) [
                'kind' => 'comment',
                'title' => 'Contact note',
                'body' => $comment->body,
                'created_at' => $comment->created_at,
                'user_name' => $comment->user?->name ?? 'Unknown',
                'url' => null,
            ]);
        }

        foreach ($contact->changes as $change) {
            $items->push((object) [
                'kind' => 'change',
                'title' => $change->summary,
                'body' => trim((string) $change->old_value.' → '.(string) $change->new_value, ' →'),
                'created_at' => $change->created_at,
                'user_name' => $change->user?->name ?? 'System',
                'url' => null,
            ]);
        }

        foreach ($contact->leads as $lead) {
            foreach ($lead->comments as $comment) {
                $items->push((object) [
                    'kind' => 'lead_comment',
                    'title' => 'Lead #'.$lead->id.' comment',
                    'body' => $comment->body,
                    'created_at' => $comment->created_at,
                    'user_name' => $comment->user?->name ?? 'Unknown',
                    'url' => route('platform.leads.edit', $lead),
                ]);
            }
            foreach ($lead->changes as $change) {
                $items->push((object) [
                    'kind' => 'lead_change',
                    'title' => 'Lead #'.$lead->id.': '.$change->summary,
                    'body' => '',
                    'created_at' => $change->created_at,
                    'user_name' => $change->user?->name ?? 'System',
                    'url' => route('platform.leads.edit', $lead),
                ]);
            }
        }

        foreach ($contact->phoneClicks as $click) {
            foreach ($click->notes as $note) {
                $items->push((object) [
                    'kind' => 'click_note',
                    'title' => 'Phone click #'.$click->id,
                    'body' => $note->body,
                    'created_at' => $note->created_at,
                    'user_name' => $note->user?->name ?? 'Unknown',
                    'url' => route('platform.phone-clicks.view', $click),
                ]);
            }
        }

        foreach ($contact->tasks as $task) {
            $items->push((object) [
                'kind' => 'task',
                'title' => 'Task: '.$task->title,
                'body' => trim($task->statusLabel().($task->result ? ' — '.$task->result : '')),
                'created_at' => $task->created_at,
                'user_name' => $task->assignee?->name ?? 'Unassigned',
                'url' => route('platform.crm.tasks.edit', $task),
            ]);
        }

        foreach ($contact->ringCentralCallsForPhone() as $call) {
            $items->push((object) [
                'kind' => 'call',
                'title' => trim(($call->direction ?? 'Call').' · '.($call->result ?? '')),
                'body' => trim((string) ($call->external_phone ?? $call->from_phone ?? '')),
                'created_at' => $call->started_at,
                'user_name' => 'RingCentral',
                'url' => null,
            ]);
        }

        return $items
            ->sortByDesc(fn (object $item) => optional($item->created_at)->timestamp ?? 0)
            ->values();
    }
}
