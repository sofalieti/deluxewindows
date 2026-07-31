<?php

use App\Models\Lead;
use App\Models\User;
use App\Orchid\Screens\Leads\LeadEditScreen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

test('editing lead data records every changed field in history', function () {
    $user = User::factory()->create();
    $lead = Lead::query()->create([
        'full_name' => 'Old Name',
        'email' => 'old@example.com',
        'phone' => '(650) 111-1111',
        'city' => 'Burlingame',
        'message' => 'Original message',
        'page_url' => 'https://www.deluxewindows.com/',
        'status' => Lead::STATUS_NEW,
    ]);
    $request = Request::create('/admin/leads/'.$lead->id.'/edit', 'POST', [
        'lead' => [
            'full_name' => 'New Name',
            'email' => 'new@example.com',
            'phone' => '(650) 222-2222',
            'city' => 'San Mateo',
            'message' => 'Updated message',
            'page_url' => 'https://www.deluxewindows.com/windows',
            'status' => Lead::STATUS_CONTACTED,
        ],
    ]);

    $this->actingAs($user);
    (new LeadEditScreen)->saveLead($lead, $request);

    $lead->refresh();
    expect($lead->full_name)->toBe('New Name')
        ->and($lead->status)->toBe(Lead::STATUS_CONTACTED)
        ->and($lead->changes)->toHaveCount(7)
        ->and($lead->changes->pluck('field')->sort()->values()->all())->toBe([
            'city',
            'email',
            'full_name',
            'message',
            'page_url',
            'phone',
            'status',
        ])
        ->and($lead->changes->firstWhere('field', 'phone')->old_value)->toBe('(650) 111-1111')
        ->and($lead->changes->firstWhere('field', 'phone')->new_value)->toBe('(650) 222-2222')
        ->and($lead->changes->every(fn ($change) => $change->user_id === $user->id))->toBeTrue();
});
