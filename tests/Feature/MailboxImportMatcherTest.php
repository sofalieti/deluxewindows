<?php

use App\Models\Contact;
use App\Models\Lead;
use App\Services\Mailbox\MailboxClientEmailDirectory;
use App\Services\Mailbox\MailboxImportMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('local services by google is imported by sender name', function () {
    $matcher = new MailboxImportMatcher;

    expect($matcher->isLocalServicesByGoogle(
        'Local Services by Google',
        'noreply@google.com',
        'New lead',
    ))->toBeTrue();
});

test('local services by google is imported by email or google subject', function () {
    $matcher = new MailboxImportMatcher;

    expect($matcher->isLocalServicesByGoogle('', 'local-services-noreply@google.com', 'Hi'))->toBeTrue()
        ->and($matcher->isLocalServicesByGoogle('', 'updates@google.com', 'Google Local Services lead'))->toBeTrue()
        ->and($matcher->isLocalServicesByGoogle('', 'updates@google.com', 'Security alert'))->toBeFalse();
});

test('a message is imported when from or to matches a contact or lead email', function () {
    Contact::query()->create([
        'full_name' => 'Jane Client',
        'email' => 'jane@example.com',
        'phone' => '6505551000',
    ]);
    Lead::query()->create([
        'full_name' => 'Bob Lead',
        'email' => 'bob@example.com',
        'phone' => '6505552000',
    ]);

    $clients = app(MailboxClientEmailDirectory::class)->normalizedSet();
    $matcher = new MailboxImportMatcher;

    expect($matcher->shouldImport('', 'jane@example.com', 'Quote', ['jane@example.com'], $clients))->toBeTrue()
        ->and($matcher->shouldImport('Us', 'info@deluxewindows.com', 'Reply', ['bob@example.com'], $clients))->toBeTrue()
        ->and($matcher->shouldImport('Stranger', 'other@example.com', 'Hi', ['other@example.com'], $clients))->toBeFalse();
});

test('mailbox messages for a client email show on that address', function () {
    $message = \App\Models\MailboxMessage::query()->create([
        'direction' => \App\Models\MailboxMessage::DIRECTION_INBOUND,
        'folder' => 'INBOX',
        'imap_uid' => 101,
        'subject' => 'Quote for windows',
        'from_email' => 'jane@example.com',
        'to' => 'info@deluxewindows.com',
        'participant_emails' => ['jane@example.com', 'info@deluxewindows.com'],
    ]);

    $found = \App\Models\MailboxMessage::query()->forParticipant('Jane@Example.com')->pluck('id');

    expect($found->all())->toContain($message->id)
        ->and(\App\Models\MailboxMessage::query()->forParticipant('nobody@example.com')->count())->toBe(0);
});

test('company and placeholder emails are not treated as clients', function () {
    Contact::query()->create([
        'full_name' => 'Internal',
        'email' => 'info@deluxewindows.com',
    ]);
    Lead::query()->create([
        'full_name' => 'Callback placeholder',
        'email' => 'callback+6505551212@noreply.deluxewindows.com',
        'phone' => '6505551212',
    ]);

    $clients = app(MailboxClientEmailDirectory::class)->normalizedSet();

    expect($clients)->toBe([]);
});
