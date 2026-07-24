<?php

use App\Services\LeadSpamGuard;

test('lead spam guard blocks cyrillic and casino stopwords but allows normal english leads', function () {
    $guard = app(LeadSpamGuard::class);

    expect($guard->inspect([
        'full_name' => 'John Smith',
        'email' => 'john@example.com',
        'phone' => '6504614446',
        'city' => 'San Jose',
        'message' => 'Need vinyl window replacement quote',
    ])['spam'])->toBeFalse();

    expect($guard->inspect([
        'full_name' => 'Иван Петров',
        'email' => 'ivan@example.com',
        'phone' => '6504614446',
        'city' => 'Moscow',
        'message' => 'Здравствуйте',
    ])['reason'])->toBe('cyrillic');

    expect($guard->inspect([
        'full_name' => 'Lucky Player',
        'email' => 'spam@example.com',
        'phone' => '6504614446',
        'city' => 'Vegas',
        'message' => 'Best online casino bonuses today',
    ])['spam'])->toBeTrue();

    expect($guard->inspect([
        'full_name' => 'Maria Lopez',
        'email' => 'maria@example.com',
        'phone' => '6504614446',
        'city' => 'Oakland',
        'message' => 'Looking for better energy efficient windows',
    ])['spam'])->toBeFalse();
});

test('lead spam guard blocks random gibberish names and emails', function () {
    $guard = app(LeadSpamGuard::class);

    expect($guard->inspect([
        'full_name' => 'kpPkLqFYbTtRsOzMZRHWmfm',
        'email' => 'bot@example.com',
        'phone' => '6504614446',
        'city' => 'San Francisco',
        'message' => 'Hello',
    ])['reason'])->toBe('gibberish:full_name');

    expect($guard->inspect([
        'full_name' => 'John Smith',
        'email' => 'xKqPmRwTnVbLzYcHdFsJa@example.com',
        'phone' => '6504614446',
        'city' => 'San Jose',
        'message' => 'Need a quote',
    ])['reason'])->toBe('gibberish:email');

    expect($guard->inspect([
        'full_name' => 'Christopher Anderson',
        'email' => 'chris.anderson@example.com',
        'phone' => '6504614446',
        'city' => 'Burlingame',
        'message' => 'Window replacement for my house',
    ])['spam'])->toBeFalse();

    expect($guard->inspect([
        'full_name' => 'Nguyen',
        'email' => 'nguyen@example.com',
        'phone' => '6504614446',
        'city' => 'San Jose',
        'message' => 'Quote please',
    ])['spam'])->toBeFalse();
});
