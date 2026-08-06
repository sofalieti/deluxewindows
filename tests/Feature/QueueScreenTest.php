<?php

use App\Models\User;
use App\Services\QueueMonitor;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('queue.default', 'database');

    CarbonImmutable::setTestNow('2026-08-06 12:00:00 UTC');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

function queueJob(array $overrides = []): void
{
    DB::table('jobs')->insert(array_merge([
        'queue' => 'default',
        'payload' => json_encode([
            'displayName' => 'App\\Jobs\\SendPhoneClickOfflineConversions',
            'data' => ['commandName' => 'App\\Jobs\\SendPhoneClickOfflineConversions'],
        ]),
        'attempts' => 0,
        'reserved_at' => null,
        'available_at' => now()->getTimestamp(),
        'created_at' => now()->getTimestamp(),
    ], $overrides));
}

function failedQueueJob(array $overrides = []): void
{
    DB::table('failed_jobs')->insert(array_merge([
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'connection' => 'database',
        'queue' => 'default',
        'payload' => json_encode(['displayName' => 'App\\Jobs\\MatchPhoneClickToRingCentral']),
        'exception' => "RuntimeException: RingCentral call log returned HTTP 500.\n#0 /app/foo.php(1)",
        'failed_at' => now(),
    ], $overrides));
}

function adminUser(): User
{
    $user = User::factory()->create();
    $user->forceFill(['permissions' => ['platform.queue' => true]])->save();

    return $user;
}

test('the monitor counts waiting, delayed, running and failed jobs', function () {
    queueJob();
    queueJob(['available_at' => now()->addMinutes(5)->getTimestamp()]);
    queueJob(['reserved_at' => now()->getTimestamp()]);
    failedQueueJob();

    $stats = app(QueueMonitor::class)->stats();

    expect($stats['waiting'])->toBe(1)
        ->and($stats['delayed'])->toBe(1)
        ->and($stats['running'])->toBe(1)
        ->and($stats['failed'])->toBe(1)
        ->and($stats['failed_today'])->toBe(1);
});

test('a job waiting for a long time is reported as stalled', function () {
    $monitor = app(QueueMonitor::class);

    queueJob(['available_at' => now()->subMinutes(2)->getTimestamp()]);
    expect($monitor->isStalled())->toBeFalse();

    DB::table('jobs')->delete();
    queueJob(['available_at' => now()->subHour()->getTimestamp()]);
    expect($monitor->isStalled())->toBeTrue()
        ->and($monitor->isStalled($monitor->stats()))->toBeTrue();
});

test('a non database driver reports itself as not inspectable', function () {
    config()->set('queue.default', 'sync');

    $monitor = app(QueueMonitor::class);

    expect($monitor->isAvailable())->toBeFalse()
        ->and($monitor->driver())->toBe('sync')
        ->and($monitor->stats()['waiting'])->toBe(0);
});

test('the queue screen lists waiting and failed jobs', function () {
    queueJob();
    failedQueueJob();

    $this->withoutMiddleware(\Orchid\Platform\Http\Middleware\Access::class)
        ->actingAs(adminUser())
        ->get(route('platform.queue'))
        ->assertOk()
        ->assertSee('SendPhoneClickOfflineConversions')
        ->assertSee('MatchPhoneClickToRingCentral')
        ->assertSee('RingCentral call log returned HTTP 500.');
});

test('a failed job can be retried from the screen', function () {
    failedQueueJob(['uuid' => 'failed-uuid-2']);

    $this->withoutMiddleware(\Orchid\Platform\Http\Middleware\Access::class)
        ->actingAs(adminUser())
        ->post(route('platform.queue', ['method' => 'retry', 'uuid' => 'failed-uuid-2']))
        ->assertRedirect();

    expect(DB::table('failed_jobs')->count())->toBe(0)
        ->and(DB::table('jobs')->count())->toBe(1);
});

test('a failed job can be deleted from the screen', function () {
    failedQueueJob(['uuid' => 'failed-uuid-1']);

    $this->withoutMiddleware(\Orchid\Platform\Http\Middleware\Access::class)
        ->actingAs(adminUser())
        ->post(route('platform.queue', ['method' => 'forget']), ['uuid' => 'failed-uuid-1'])
        ->assertRedirect();

    expect(DB::table('failed_jobs')->count())->toBe(0);
});
