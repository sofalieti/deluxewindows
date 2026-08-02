<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ringcentral_calls') && ! Schema::hasColumn('ringcentral_calls', 'recording_id')) {
            Schema::table('ringcentral_calls', function (Blueprint $table): void {
                $table->string('recording_id', 64)->nullable()->after('external_phone')->index();
            });
        }

        if (Schema::hasTable('phone_clicks') && ! Schema::hasColumn('phone_clicks', 'ringcentral_recording_id')) {
            Schema::table('phone_clicks', function (Blueprint $table): void {
                $table->string('ringcentral_recording_id', 64)->nullable()->after('ringcentral_call_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ringcentral_calls') && Schema::hasColumn('ringcentral_calls', 'recording_id')) {
            Schema::table('ringcentral_calls', function (Blueprint $table): void {
                $table->dropColumn('recording_id');
            });
        }

        if (Schema::hasTable('phone_clicks') && Schema::hasColumn('phone_clicks', 'ringcentral_recording_id')) {
            Schema::table('phone_clicks', function (Blueprint $table): void {
                $table->dropColumn('ringcentral_recording_id');
            });
        }
    }
};
