<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Read-only view of the queue for the admin panel: what is waiting, what is
 * running right now and what has already failed.
 *
 * Only the database queue driver can be inspected this way — the tables are the
 * queue. Redis or SQS would need their own client, so the screen says so instead
 * of showing misleading zeroes.
 */
class QueueMonitor
{
    public function connection(): string
    {
        return (string) config('queue.default');
    }

    public function driver(): string
    {
        return (string) config('queue.connections.'.$this->connection().'.driver', $this->connection());
    }

    public function isAvailable(): bool
    {
        return $this->driver() === 'database' && Schema::hasTable('jobs');
    }

    /**
     * @return array{
     *     waiting: int,
     *     delayed: int,
     *     running: int,
     *     failed: int,
     *     failed_today: int,
     *     oldest_waiting_at: ?CarbonImmutable,
     *     oldest_waiting_seconds: ?int
     * }
     */
    public function stats(): array
    {
        $empty = [
            'waiting' => 0,
            'delayed' => 0,
            'running' => 0,
            'failed' => 0,
            'failed_today' => 0,
            'oldest_waiting_at' => null,
            'oldest_waiting_seconds' => null,
        ];

        if (! $this->isAvailable()) {
            return $empty;
        }

        $now = now()->getTimestamp();

        $oldest = DB::table('jobs')
            ->whereNull('reserved_at')
            ->where('available_at', '<=', $now)
            ->min('available_at');

        $oldestAt = $oldest !== null
            ? CarbonImmutable::createFromTimestamp((int) $oldest, config('app.timezone'))
            : null;

        return [
            'waiting' => DB::table('jobs')->whereNull('reserved_at')->where('available_at', '<=', $now)->count(),
            'delayed' => DB::table('jobs')->whereNull('reserved_at')->where('available_at', '>', $now)->count(),
            'running' => DB::table('jobs')->whereNotNull('reserved_at')->count(),
            'failed' => $this->failedJobsAvailable() ? DB::table('failed_jobs')->count() : 0,
            'failed_today' => $this->failedJobsAvailable()
                ? DB::table('failed_jobs')->where('failed_at', '>=', now()->subDay())->count()
                : 0,
            'oldest_waiting_at' => $oldestAt,
            'oldest_waiting_seconds' => $oldestAt !== null ? $now - (int) $oldest : null,
        ];
    }

    /**
     * A job that has been runnable for a long time means nothing is consuming the
     * queue — the single most useful signal on this screen.
     *
     * @param  array<string, mixed>|null  $stats  already computed stats, to avoid a second round of queries
     */
    public function isStalled(?array $stats = null, int $thresholdSeconds = 900): bool
    {
        $seconds = ($stats ?? $this->stats())['oldest_waiting_seconds'] ?? null;

        return $seconds !== null && $seconds >= $thresholdSeconds;
    }

    /**
     * When workers were last told to restart. Reading it back is the only way to
     * tell from the panel that the restart button actually reached the cache.
     */
    public function lastRestartSignalAt(): ?CarbonImmutable
    {
        $timestamp = Cache::get('illuminate:queue:restart');

        return is_numeric($timestamp)
            ? CarbonImmutable::createFromTimestamp((int) $timestamp, config('app.timezone'))
            : null;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function pending(int $limit = 100): Collection
    {
        if (! $this->isAvailable()) {
            return collect();
        }

        $now = now()->getTimestamp();

        return DB::table('jobs')
            ->orderBy('available_at')
            ->limit($limit)
            ->get()
            ->map(fn (object $job): array => [
                'id' => (int) $job->id,
                'queue' => (string) $job->queue,
                'name' => $this->jobName($job->payload),
                'attempts' => (int) $job->attempts,
                'state' => $this->state($job, $now),
                'available_at' => CarbonImmutable::createFromTimestamp((int) $job->available_at, config('app.timezone')),
                'created_at' => CarbonImmutable::createFromTimestamp((int) $job->created_at, config('app.timezone')),
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function failed(int $limit = 100): Collection
    {
        if (! $this->failedJobsAvailable()) {
            return collect();
        }

        return DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit($limit)
            ->get()
            ->map(fn (object $job): array => [
                'uuid' => (string) $job->uuid,
                'queue' => (string) $job->queue,
                'name' => $this->jobName($job->payload),
                'failed_at' => CarbonImmutable::parse($job->failed_at)->setTimezone(config('app.timezone')),
                'exception' => $this->exceptionSummary((string) $job->exception),
            ]);
    }

    private function failedJobsAvailable(): bool
    {
        return Schema::hasTable('failed_jobs');
    }

    private function state(object $job, int $now): string
    {
        if ($job->reserved_at !== null) {
            return 'running';
        }

        return (int) $job->available_at > $now ? 'delayed' : 'waiting';
    }

    private function jobName(?string $payload): string
    {
        $decoded = json_decode((string) $payload, true);
        if (! is_array($decoded)) {
            return 'Unknown job';
        }

        $name = (string) ($decoded['displayName'] ?? data_get($decoded, 'data.commandName') ?? '');

        return $name !== '' ? $name : 'Unknown job';
    }

    private function exceptionSummary(string $exception): string
    {
        return Str::limit(trim((string) Str::of($exception)->explode("\n")->first()), 300);
    }
}
