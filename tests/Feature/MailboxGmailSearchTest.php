<?php

use App\Services\Mailbox\MailboxGmailSearch;

test('gmail search prefers recent mail and quotes the raw query for IMAP', function () {
    $search = new MailboxGmailSearch;

    $clients = $search->forClientEmails(['jane@example.com', 'bob@example.com'], 45);
    $lsa = $search->forLocalServices(45);

    expect($clients)->toStartWith('newer_than:45d ')
        ->and($clients)->toContain('from:jane@example.com')
        ->and($clients)->toContain('to:bob@example.com')
        ->and($lsa)->toBe('newer_than:45d (from:local-services OR from:localservices OR subject:Local)')
        ->and($search->toImapWhere($lsa))->toBe(
            'CUSTOM X-GM-RAW "newer_than:45d (from:local-services OR from:localservices OR subject:Local)"'
        );
});

test('all-time gmail search omits newer_than so older history can still be pulled later', function () {
    $search = new MailboxGmailSearch;

    expect($search->forLocalServices(0))->toBe(
        'from:local-services OR from:localservices OR subject:Local'
    );
});
