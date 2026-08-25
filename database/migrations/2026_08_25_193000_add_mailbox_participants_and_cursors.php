<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\MailboxMessage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mailbox_messages', function (Blueprint $table): void {
            $table->json('participant_emails')->nullable()->after('cc');
        });

        Schema::table('mailbox_settings', function (Blueprint $table): void {
            $table->json('folder_cursors')->nullable()->after('last_sync_at');
        });

        if (Schema::hasTable('mailbox_messages')) {
            MailboxMessage::query()->orderBy('id')->each(function (MailboxMessage $message): void {
                $emails = [];
                foreach ([$message->from_email, $message->to, $message->cc] as $raw) {
                    if (preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', (string) $raw, $matches)) {
                        foreach ($matches[0] as $email) {
                            $normalized = Contact::normalizeEmail($email);
                            if ($normalized !== null) {
                                $emails[$normalized] = true;
                            }
                        }
                    }
                }

                $message->forceFill([
                    'participant_emails' => array_keys($emails),
                ])->save();
            });
        }
    }

    public function down(): void
    {
        Schema::table('mailbox_messages', function (Blueprint $table): void {
            $table->dropColumn('participant_emails');
        });

        Schema::table('mailbox_settings', function (Blueprint $table): void {
            $table->dropColumn('folder_cursors');
        });
    }
};
