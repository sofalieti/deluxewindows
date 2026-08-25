<?php

use App\Services\Mailbox\MailboxFolderPicker;

test('gmail sent and all mail folders are selected for sync', function () {
    $picker = new MailboxFolderPicker;

    expect($picker->shouldSync('INBOX'))->toBeTrue()
        ->and($picker->shouldSync('[Gmail]/All Mail'))->toBeTrue()
        ->and($picker->shouldSync('[Gmail].All Mail'))->toBeTrue()
        ->and($picker->shouldSync('[Gmail]/Sent Mail'))->toBeTrue()
        ->and($picker->shouldSync('INBOX.Sent'))->toBeTrue()
        ->and($picker->shouldSync('[Gmail]/Spam'))->toBeFalse()
        ->and($picker->shouldSync('[Gmail]/Trash'))->toBeFalse()
        ->and($picker->shouldSync('[Gmail]/Drafts'))->toBeFalse();
});
