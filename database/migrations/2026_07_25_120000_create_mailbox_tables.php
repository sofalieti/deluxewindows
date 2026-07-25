<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailbox_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('scope')->unique()->default('default');
            $table->boolean('enabled')->default(false);
            $table->string('imap_host')->default('imap.gmail.com');
            $table->unsignedSmallInteger('imap_port')->default(993);
            $table->string('imap_encryption', 16)->default('ssl');
            $table->string('smtp_host')->default('smtp.gmail.com');
            $table->unsignedSmallInteger('smtp_port')->default(587);
            $table->string('smtp_encryption', 16)->default('tls');
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->string('folder')->default('INBOX');
            $table->string('subject_filter')->default('Deluxewindows');
            $table->timestamp('last_sync_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('mailbox_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('direction', 16)->default('inbound');
            $table->string('folder')->default('INBOX');
            $table->unsignedBigInteger('imap_uid')->nullable();
            $table->string('message_id')->nullable()->index();
            $table->string('in_reply_to')->nullable();
            $table->string('subject')->nullable();
            $table->string('from_email')->nullable()->index();
            $table->string('from_name')->nullable();
            $table->text('to')->nullable();
            $table->text('cc')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->string('snippet', 500)->nullable();
            $table->longText('body_text')->nullable();
            $table->longText('body_html')->nullable();
            $table->boolean('has_attachments')->default(false);
            $table->longText('raw_headers')->nullable();
            $table->boolean('is_read_local')->default(false);
            $table->timestamps();

            $table->unique(['folder', 'imap_uid'], 'mailbox_messages_folder_uid_unique');
            // Outbound rows use null imap_uid; DB allows multiple NULLs in this unique index.
        });

        Schema::create('mailbox_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mailbox_message_id')->constrained('mailbox_messages')->cascadeOnDelete();
            $table->string('filename');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('disk_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailbox_attachments');
        Schema::dropIfExists('mailbox_messages');
        Schema::dropIfExists('mailbox_settings');
    }
};
