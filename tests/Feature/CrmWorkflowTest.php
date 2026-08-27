<?php

use App\Mail\CrmTaskDigestMail;
use App\Models\Contact;
use App\Models\CrmTask;
use App\Models\Lead;
use App\Models\PhoneClick;
use App\Models\User;
use App\Services\ContactFromPhoneClickService;
use App\Services\CrmTaskAutomation;
use App\Services\CrmTaskService;
use App\Services\TrafficSourceVisibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Orchid\Platform\Http\Middleware\Access;

uses(RefreshDatabase::class);

function crmManager(array $extraPermissions = []): User
{
    $user = User::factory()->create();
    $user->forceFill([
        'permissions' => array_merge(
            adminTrafficPermissions(),
            [
                CrmTask::PERMISSION => true,
            ],
            $extraPermissions
        ),
    ])->save();

    return $user;
}

test('a no_call phone click creates a callback task', function () {
    $click = PhoneClick::query()->create([
        'phone' => '+16504614446',
        'source_label' => 'header-phone',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_NO_CALL,
    ]);

    $task = app(CrmTaskAutomation::class)->onPhoneClickMatched($click);

    expect($task)->not->toBeNull()
        ->and($task->type)->toBe(CrmTask::TYPE_CALLBACK)
        ->and($task->status)->toBe(CrmTask::STATUS_OPEN)
        ->and($task->title)->toBe('Phone click with no call')
        ->and($task->subject_id)->toBe($click->id)
        ->and(CrmTask::query()->count())->toBe(1);
});

test('a connected phone click is marked reached and does not create a task', function () {
    $click = PhoneClick::query()->create([
        'phone' => '+16504614446',
        'source_label' => 'header-phone',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_FOUND,
        'ringcentral_result' => 'Accepted',
        'ringcentral_duration' => 48,
        'ringcentral_from_phone' => '+16505551000',
        'ringcentral_direction' => 'Inbound',
    ]);

    $task = app(CrmTaskAutomation::class)->onPhoneClickMatched($click);

    expect($task)->toBeNull()
        ->and($click->refresh()->handling_status)->toBe(PhoneClick::HANDLING_REACHED)
        ->and(CrmTask::query()->count())->toBe(0);
});

test('a missed phone click creates a callback task and is marked no answer', function () {
    $click = PhoneClick::query()->create([
        'phone' => '+16504614446',
        'source_label' => 'header-phone',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_FOUND,
        'ringcentral_result' => 'Missed',
        'ringcentral_duration' => 0,
        'ringcentral_from_phone' => '+16505551000',
        'ringcentral_direction' => 'Inbound',
    ]);

    $task = app(CrmTaskAutomation::class)->onPhoneClickMatched($click);

    expect($task)->not->toBeNull()
        ->and($task->title)->toBe('Missed call — call back')
        ->and($click->refresh()->handling_status)->toBe(PhoneClick::HANDLING_NO_ANSWER);
});

test('a new lead creates a call task and a spam lead does not', function () {
    $automation = app(CrmTaskAutomation::class);

    $lead = Lead::query()->create([
        'full_name' => 'New Client',
        'email' => 'new-client@example.com',
        'phone' => '6505551000',
    ]);
    $spam = Lead::query()->create([
        'full_name' => 'Spam Bot',
        'email' => 'spam-bot@example.com',
        'phone' => '6505551999',
        'status' => Lead::STATUS_SPAM,
    ]);

    $task = $automation->onLeadCreated($lead);
    $spamTask = $automation->onLeadCreated($spam);

    expect($task)->not->toBeNull()
        ->and($task->type)->toBe(CrmTask::TYPE_CALL)
        ->and($task->title)->toBe('Call new lead')
        ->and($spamTask)->toBeNull()
        ->and(CrmTask::query()->where('subject_id', $spam->id)->exists())->toBeFalse();
});

test('leaving new status auto-closes the open call task', function () {
    $lead = Lead::query()->create([
        'full_name' => 'Status Change Client',
        'email' => 'status-change@example.com',
        'phone' => '6505552000',
    ]);
    $automation = app(CrmTaskAutomation::class);
    $task = $automation->onLeadCreated($lead);

    $lead->status = Lead::STATUS_CONTACTED;
    $lead->save();
    $automation->onLeadStatusChanged($lead, Lead::STATUS_NEW, Lead::STATUS_CONTACTED);

    expect($task->refresh()->status)->toBe(CrmTask::STATUS_DONE)
        ->and($task->result)->toBe('Lead status: Contacted');
});

test('a phone click links to the single matching contact by normalized phone', function () {
    $contact = Contact::query()->create([
        'full_name' => 'Unique Phone Client',
        'phone' => '(650) 555-3000',
    ]);
    $click = PhoneClick::query()->create([
        'phone' => '650-555-3000',
        'source_label' => 'header-phone',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_PENDING,
    ]);

    $linked = app(ContactFromPhoneClickService::class)->attachNewClick($click);

    expect($linked?->id)->toBe($contact->id)
        ->and($click->refresh()->contact_id)->toBe($contact->id);
});

test('a phone click is not linked when two contacts share the same phone', function () {
    Contact::query()->create([
        'full_name' => 'First Duplicate',
        'phone' => '6505554000',
    ]);
    Contact::query()->create([
        'full_name' => 'Second Duplicate',
        'phone' => '6505554000',
    ]);
    $click = PhoneClick::query()->create([
        'phone' => '6505554000',
        'source_label' => 'header-phone',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_PENDING,
    ]);

    expect(app(ContactFromPhoneClickService::class)->attachNewClick($click))->toBeNull()
        ->and($click->refresh()->contact_id)->toBeNull();
});

test('completing a task writes completed_by, completed_at, result and history', function () {
    $user = User::factory()->create();
    $task = app(CrmTaskService::class)->create([
        'title' => 'Call the client back',
        'type' => CrmTask::TYPE_CALLBACK,
        'assigned_to' => $user->id,
        'created_by' => $user->id,
    ]);

    $completed = app(CrmTaskService::class)->complete($task, $user, 'Left voicemail');

    expect($completed->status)->toBe(CrmTask::STATUS_DONE)
        ->and($completed->completed_by)->toBe($user->id)
        ->and($completed->completed_at)->not->toBeNull()
        ->and($completed->result)->toBe('Left voicemail')
        ->and($completed->events)->toHaveCount(1)
        ->and($completed->events->first()->action)->toBe(\App\Models\CrmTaskEvent::ACTION_COMPLETED)
        ->and($completed->events->first()->user_id)->toBe($user->id)
        ->and($completed->events->first()->comment)->toBe('Left voicemail');

    $reopened = app(CrmTaskService::class)->reopen($completed, $user);

    expect($reopened->status)->toBe(CrmTask::STATUS_OPEN)
        ->and($reopened->completed_by)->toBeNull()
        ->and($reopened->completed_at)->toBeNull()
        ->and($reopened->result)->toBe('Left voicemail')
        ->and($reopened->events()->count())->toBe(2)
        ->and($reopened->events()->orderBy('id')->first()->action)->toBe(\App\Models\CrmTaskEvent::ACTION_COMPLETED)
        ->and($reopened->events()->orderByDesc('id')->first()->action)->toBe(\App\Models\CrmTaskEvent::ACTION_REOPENED);
});

test('closing a task without a comment is rejected', function () {
    $user = User::factory()->create();
    $task = app(CrmTaskService::class)->create([
        'title' => 'Needs a comment',
        'type' => CrmTask::TYPE_CALL,
        'assigned_to' => $user->id,
        'created_by' => $user->id,
    ]);

    expect(fn () => app(CrmTaskService::class)->complete($task, $user, '   '))
        ->toThrow(InvalidArgumentException::class, 'A comment is required when closing a task.');
});

test('task list complete modal requires a comment', function () {
    $manager = crmManager();
    $task = app(CrmTaskService::class)->create([
        'title' => 'Modal close needs comment',
        'type' => CrmTask::TYPE_CALL,
        'assigned_to' => $manager->id,
        'due_at' => now()->addHour(),
    ]);

    $this->withoutMiddleware(Access::class)
        ->actingAs($manager)
        ->post(route('platform.crm.tasks', ['method' => 'complete', 'task' => $task->id]), [
            'result' => '',
        ])
        ->assertSessionHasErrors('result');

    expect($task->refresh()->status)->toBe(CrmTask::STATUS_OPEN);

    $this->withoutMiddleware(Access::class)
        ->actingAs($manager)
        ->post(route('platform.crm.tasks', ['method' => 'complete', 'task' => $task->id]), [
            'result' => 'Spoke with homeowner',
        ])
        ->assertRedirect();

    expect($task->refresh()->status)->toBe(CrmTask::STATUS_DONE)
        ->and($task->result)->toBe('Spoke with homeowner')
        ->and($task->events()->count())->toBe(1);
});

test('work queue shows only the current user tasks without tasks.all and respects traffic visibility', function () {
    $manager = crmManager();
    $other = User::factory()->create();

    app(CrmTaskService::class)->create([
        'title' => 'Mine Task AlphaUnique',
        'type' => CrmTask::TYPE_CALL,
        'assigned_to' => $manager->id,
        'due_at' => now()->addHours(2),
    ]);
    app(CrmTaskService::class)->create([
        'title' => 'Other Task BetaUnique',
        'type' => CrmTask::TYPE_CALL,
        'assigned_to' => $other->id,
        'due_at' => now()->addHours(2),
    ]);

    Lead::query()->create([
        'full_name' => 'Google WorkQueue Lead',
        'email' => 'google-workqueue@example.com',
        'phone' => '5551111111',
        'utm_source' => 'google',
        'utm_medium' => 'cpc',
        'meta' => ['gclid' => 'gclid-workqueue'],
        'status' => Lead::STATUS_NEW,
    ]);
    Lead::query()->create([
        'full_name' => 'Bing WorkQueue Lead',
        'email' => 'bing-workqueue@example.com',
        'phone' => '5552222222',
        'utm_source' => 'bing',
        'utm_medium' => 'cpc',
        'meta' => ['msclkid' => 'ms-workqueue'],
        'status' => Lead::STATUS_NEW,
    ]);

    $this->withoutMiddleware(Access::class)
        ->actingAs($manager)
        ->get(route('platform.crm.work'))
        ->assertOk()
        ->assertSee('Mine Task AlphaUnique')
        ->assertDontSee('Other Task BetaUnique');

    $limited = User::factory()->create();
    $limited->forceFill([
        'permissions' => array_merge(
            [
                CrmTask::PERMISSION => true,
                'platform.leads' => true,
                TrafficSourceVisibility::permission(TrafficSourceVisibility::SECTION_LEADS, 'adwords') => true,
            ],
            TrafficSourceVisibility::sourceGrantPayload(TrafficSourceVisibility::SECTION_PHONE_CLICKS)
        ),
    ])->save();

    $this->withoutMiddleware(Access::class)
        ->actingAs($limited)
        ->get(route('platform.crm.work'))
        ->assertOk()
        ->assertSee('Google WorkQueue Lead')
        ->assertDontSee('Bing WorkQueue Lead');
});

test('task list without tasks.all hides other managers tasks', function () {
    $manager = crmManager();
    $other = User::factory()->create();

    app(CrmTaskService::class)->create([
        'title' => 'Visible Own Task Gamma',
        'assigned_to' => $manager->id,
    ]);
    app(CrmTaskService::class)->create([
        'title' => 'Hidden Other Task Delta',
        'assigned_to' => $other->id,
    ]);

    $this->withoutMiddleware(Access::class)
        ->actingAs($manager)
        ->get(route('platform.crm.tasks'))
        ->assertOk()
        ->assertSee('Visible Own Task Gamma')
        ->assertDontSee('Hidden Other Task Delta');
});

test('a final handling status auto-closes open click tasks', function () {
    $click = PhoneClick::query()->create([
        'phone' => '+16504614446',
        'source_label' => 'header-phone',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_NO_CALL,
    ]);
    $automation = app(CrmTaskAutomation::class);
    $task = $automation->onPhoneClickMatched($click);

    $click->handling_status = PhoneClick::HANDLING_JUNK;
    $click->save();
    $automation->onHandlingStatusChanged($click, PhoneClick::HANDLING_NEW, PhoneClick::HANDLING_JUNK);

    expect($task->refresh()->status)->toBe(CrmTask::STATUS_DONE);
});

test('the daily digest emails overdue and due-today tasks', function () {
    Mail::fake();
    \Carbon\CarbonImmutable::setTestNow('2026-08-25 16:00:00');
    \Illuminate\Support\Carbon::setTestNow('2026-08-25 16:00:00');

    $manager = User::factory()->create(['email' => 'manager-digest@example.com']);
    app(CrmTaskService::class)->create([
        'title' => 'Overdue digest task',
        'assigned_to' => $manager->id,
        'due_at' => now()->subHour(),
    ]);
    app(CrmTaskService::class)->create([
        'title' => 'Today digest task',
        'assigned_to' => $manager->id,
        'due_at' => now()->endOfDay()->subHour(),
    ]);

    Artisan::call('crm:task-digest');

    Mail::assertSent(CrmTaskDigestMail::class, function (CrmTaskDigestMail $mail) use ($manager) {
        return $mail->hasTo($manager->email)
            && $mail->overdue->contains(fn (CrmTask $task) => $task->title === 'Overdue digest task')
            && $mail->today->contains(fn (CrmTask $task) => $task->title === 'Today digest task');
    });

    \Carbon\CarbonImmutable::setTestNow();
    \Illuminate\Support\Carbon::setTestNow();
});
