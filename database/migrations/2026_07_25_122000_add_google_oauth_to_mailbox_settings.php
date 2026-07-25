<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mailbox_settings', function (Blueprint $table): void {
            $table->string('auth_mode', 16)->default('oauth')->after('enabled');
            $table->text('google_client_id')->nullable()->after('password');
            $table->text('google_client_secret')->nullable()->after('google_client_id');
            $table->text('google_refresh_token')->nullable()->after('google_client_secret');
            $table->text('google_access_token')->nullable()->after('google_refresh_token');
            $table->timestamp('google_token_expires_at')->nullable()->after('google_access_token');
            $table->string('google_connected_email')->nullable()->after('google_token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('mailbox_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'auth_mode',
                'google_client_id',
                'google_client_secret',
                'google_refresh_token',
                'google_access_token',
                'google_token_expires_at',
                'google_connected_email',
            ]);
        });
    }
};
