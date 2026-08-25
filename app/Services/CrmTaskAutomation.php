<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CrmTask;
use App\Models\Lead;
use App\Models\PhoneClick;
use App\Models\User;

final class CrmTaskAutomation
{
    public function __construct(
        private readonly CrmTaskService $tasks,
        private readonly ContactFromPhoneClickService $clickContacts,
    ) {
    }

    public function onLeadCreated(Lead $lead): ?CrmTask
    {
        if ($lead->isSpam()) {
            return null;
        }

        if ($this->tasks->findOpenOfType($lead, CrmTask::TYPE_CALL) !== null) {
            return null;
        }

        return $this->tasks->create([
            'subject' => $lead,
            'contact_id' => $lead->contact_id,
            'assigned_to' => $lead->assigned_to,
            'title' => 'Call new lead',
            'description' => trim((string) $lead->full_name).' · '.trim((string) $lead->phone),
            'type' => CrmTask::TYPE_CALL,
            'priority' => CrmTask::PRIORITY_HIGH,
            'due_at' => now()->addMinutes((int) config('crm.new_lead_task_minutes', 15)),
        ]);
    }

    public function onLeadStatusChanged(Lead $lead, string $from, string $to): void
    {
        if ($from === Lead::STATUS_NEW && $to !== Lead::STATUS_NEW) {
            $this->tasks->closeOpenFor($lead, CrmTask::TYPE_CALL, 'Lead status: '.$lead->statusLabel());
        }
    }

    public function onPhoneClickMatched(PhoneClick $click): ?CrmTask
    {
        $this->clickContacts->attachNewClick($click->refresh());

        if ($click->ringcentral_status === PhoneClick::RINGCENTRAL_NO_CALL) {
            return $this->ensureCallbackTask(
                $click,
                'Phone click with no call',
                (int) config('crm.no_call_task_minutes', 30),
            );
        }

        if ($click->ringcentral_status !== PhoneClick::RINGCENTRAL_FOUND) {
            return null;
        }

        if ($click->isConnectedCall()) {
            if ($click->handling_status === PhoneClick::HANDLING_NEW) {
                $click->handling_status = PhoneClick::HANDLING_REACHED;
                $click->save();
            }

            return null;
        }

        if ($click->handling_status === PhoneClick::HANDLING_NEW) {
            $click->handling_status = PhoneClick::HANDLING_NO_ANSWER;
            $click->save();
        }

        return $this->ensureCallbackTask(
            $click,
            'Missed call — call back',
            (int) config('crm.missed_call_task_minutes', 15),
        );
    }

    public function onHandlingStatusChanged(PhoneClick $click, string $from, string $to, ?User $user = null): void
    {
        if ($from === $to) {
            return;
        }

        if ($click->hasFinalHandlingStatus()) {
            $this->tasks->closeOpenFor($click, null, 'Handling: '.$click->handlingStatusLabel());
        }
    }

    private function ensureCallbackTask(PhoneClick $click, string $title, int $dueMinutes): CrmTask
    {
        $existing = $this->tasks->findOpenOfType($click, CrmTask::TYPE_CALLBACK);
        if ($existing !== null) {
            return $existing;
        }

        return $this->tasks->create([
            'subject' => $click,
            'contact_id' => $click->contact_id,
            'assigned_to' => $click->assigned_to,
            'title' => $title,
            'description' => trim((string) ($click->ringCentralClientPhone() ?: $click->phone)),
            'type' => CrmTask::TYPE_CALLBACK,
            'priority' => CrmTask::PRIORITY_HIGH,
            'due_at' => now()->addMinutes($dueMinutes),
        ]);
    }
}
