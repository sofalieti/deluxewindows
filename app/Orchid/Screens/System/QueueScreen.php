<?php

declare(strict_types=1);

namespace App\Orchid\Screens\System;

use App\Services\QueueMonitor;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Repository;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class QueueScreen extends Screen
{
    private QueueMonitor $monitor;

    /** @var array<string, mixed> */
    private array $stats = [];

    public function query(QueueMonitor $monitor): iterable
    {
        $this->monitor = $monitor;
        $this->stats = $monitor->stats();

        if (! $monitor->isAvailable()) {
            Toast::warning(sprintf(
                'The "%s" queue driver cannot be inspected here — only the database driver stores jobs in a table.',
                $monitor->driver()
            ));
        }

        return [
            'metrics' => [
                'waiting' => ['value' => (string) $this->stats['waiting']],
                'delayed' => ['value' => (string) $this->stats['delayed']],
                'running' => ['value' => (string) $this->stats['running']],
                'failed' => ['value' => (string) $this->stats['failed']],
            ],
            'pending' => $monitor->pending()->map(fn (array $row) => new Repository($row)),
            'failed' => $monitor->failed()->map(fn (array $row) => new Repository($row)),
        ];
    }

    public function name(): ?string
    {
        return 'Queue';
    }

    public function description(): ?string
    {
        if (! isset($this->monitor)) {
            return null;
        }

        $parts = ['Driver: '.$this->monitor->driver().'.'];

        $oldest = $this->stats['oldest_waiting_at'] ?? null;
        if ($oldest !== null) {
            $parts[] = 'Oldest job has been waiting since '.$oldest->format('Y-m-d H:i')
                .' ('.$oldest->diffForHumans(syntax: CarbonInterface::DIFF_ABSOLUTE).').';
        }

        if ($this->monitor->isStalled($this->stats)) {
            $parts[] = 'Nothing seems to be consuming the queue — check that queue:work is running.';
        }

        if (($this->stats['failed_today'] ?? 0) > 0) {
            $parts[] = $this->stats['failed_today'].' job(s) failed in the last 24 hours.';
        }

        return implode(' ', $parts);
    }

    public function permission(): ?iterable
    {
        return ['platform.queue'];
    }

    public function commandBar(): iterable
    {
        return [
            Button::make('Retry all failed')
                ->icon('bs.arrow-repeat')
                ->method('retryAll')
                ->confirm('Push every failed job back onto the queue?'),

            Button::make('Restart workers')
                ->icon('bs.power')
                ->method('restartWorkers')
                ->confirm('Signal every queue worker to finish its current job and restart? Use this after a deploy.'),

            Button::make('Delete all failed')
                ->icon('bs.trash')
                ->type(Color::DANGER)
                ->method('flushFailed')
                ->confirm('Permanently delete every failed job? They cannot be retried afterwards.'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::view('admin.queue.assets'),

            Layout::metrics([
                'Waiting' => 'metrics.waiting',
                'Delayed' => 'metrics.delayed',
                'Running' => 'metrics.running',
                'Failed' => 'metrics.failed',
            ]),

            Layout::tabs([
                'Waiting jobs' => Layout::table('pending', [
                    TD::make('name', 'Job')
                        ->render(fn (Repository $job): string => '<span class="queue-table__job">'
                            .e($job->get('name')).'</span>'),

                    TD::make('queue', 'Queue')
                        ->render(fn (Repository $job): string => '<span class="queue-table__queue">'
                            .e($job->get('queue')).'</span>'),

                    TD::make('state', 'State')
                        ->render(fn (Repository $job): string => $this->stateBadge((string) $job->get('state'))),

                    TD::make('attempts', 'Attempts')
                        ->align(TD::ALIGN_RIGHT)
                        ->render(fn (Repository $job): string => (string) $job->get('attempts')),

                    TD::make('available_at', 'Runs at')
                        ->render(fn (Repository $job): string => '<span class="queue-table__time">'
                            .$job->get('available_at')->format('Y-m-d H:i:s').'</span>'),

                    TD::make('created_at', 'Queued')
                        ->render(fn (Repository $job): string => '<span class="queue-table__time">'
                            .e($job->get('created_at')->diffForHumans()).'</span>'),
                ]),

                'Failed jobs' => Layout::table('failed', [
                    TD::make('name', 'Job')
                        ->render(fn (Repository $job): string => '<span class="queue-table__job">'
                            .e($job->get('name')).'</span>'),

                    TD::make('queue', 'Queue')
                        ->render(fn (Repository $job): string => '<span class="queue-table__queue">'
                            .e($job->get('queue')).'</span>'),

                    TD::make('failed_at', 'Failed')
                        ->render(fn (Repository $job): string => '<span class="queue-table__time">'
                            .$job->get('failed_at')->format('Y-m-d H:i:s')
                            .'<span class="queue-table__time-ago">'.e($job->get('failed_at')->diffForHumans())
                            .'</span></span>'),

                    TD::make('exception', 'Error')
                        ->render(fn (Repository $job): string => '<span class="queue-table__error">'
                            .e($job->get('exception')).'</span>'),

                    TD::make('actions', '')
                        ->align(TD::ALIGN_RIGHT)
                        ->render(fn (Repository $job) => DropDown::make()
                            ->icon('bs.three-dots-vertical')
                            ->list([
                                Button::make('Retry')
                                    ->icon('bs.arrow-repeat')
                                    ->method('retry', ['uuid' => $job->get('uuid')]),

                                Button::make('Delete')
                                    ->icon('bs.trash')
                                    ->type(Color::DANGER)
                                    ->confirm('Delete this failed job?')
                                    ->method('forget', ['uuid' => $job->get('uuid')]),
                            ])),
                ]),
            ]),
        ];
    }

    public function retry(Request $request): void
    {
        $uuid = (string) $request->input('uuid');

        Artisan::call('queue:retry', ['id' => [$uuid]]);

        Toast::info('The job was pushed back onto the queue.');
    }

    public function retryAll(): void
    {
        Artisan::call('queue:retry', ['id' => ['all']]);

        Toast::info('All failed jobs were pushed back onto the queue.');
    }

    public function forget(Request $request): void
    {
        Artisan::call('queue:forget', ['id' => (string) $request->input('uuid')]);

        Toast::info('The failed job was deleted.');
    }

    public function flushFailed(): void
    {
        Artisan::call('queue:flush');

        Toast::info('All failed jobs were deleted.');
    }

    public function restartWorkers(): void
    {
        Artisan::call('queue:restart');

        Toast::info('Workers will restart after finishing their current job.');
    }

    private function stateBadge(string $state): string
    {
        return '<span class="queue-table__state queue-table__state--'.e($state).'">'.e($state).'</span>';
    }
}
