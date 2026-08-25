<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\CrmTaskDigestMail;
use App\Models\CrmTask;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CrmTaskDigestCommand extends Command
{
    protected $signature = 'crm:task-digest';

    protected $description = 'Email each manager their overdue and due-today CRM tasks';

    public function handle(): int
    {
        $start = now()->startOfDay();
        $end = now()->endOfDay();

        $assigneeIds = CrmTask::query()
            ->open()
            ->whereNotNull('assigned_to')
            ->where(function ($query) use ($start, $end): void {
                $query->where('due_at', '<', now())
                    ->orWhereBetween('due_at', [$start, $end]);
            })
            ->distinct()
            ->pluck('assigned_to');

        $sent = 0;

        User::query()->whereIn('id', $assigneeIds)->each(function (User $user) use ($start, $end, &$sent): void {
            $overdue = CrmTask::query()
                ->with(['subject', 'contact'])
                ->assignedTo($user)
                ->overdue()
                ->orderBy('due_at')
                ->get();

            $today = CrmTask::query()
                ->with(['subject', 'contact'])
                ->assignedTo($user)
                ->open()
                ->dueWithin($start, $end)
                ->orderBy('due_at')
                ->get()
                ->filter(fn (CrmTask $task) => ! $task->isOverdue())
                ->values();

            if ($overdue->isEmpty() && $today->isEmpty()) {
                return;
            }

            Mail::to($user->email)->send(new CrmTaskDigestMail($user, $overdue, $today));
            $sent++;
        });

        $this->info(sprintf('Sent %d task digest email(s).', $sent));

        return self::SUCCESS;
    }
}
