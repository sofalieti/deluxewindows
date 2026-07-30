<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phone_clicks', function (Blueprint $table): void {
            $table->string('ringcentral_status', 24)->default('not_checked')->after('meta')->index();
            $table->timestamp('ringcentral_checked_at')->nullable()->after('ringcentral_status');
            $table->unsignedTinyInteger('ringcentral_attempts')->default(0)->after('ringcentral_checked_at');
            $table->string('ringcentral_call_id', 120)->nullable()->after('ringcentral_attempts')->unique();
            $table->string('ringcentral_session_id', 120)->nullable()->after('ringcentral_call_id');
            $table->string('ringcentral_result', 120)->nullable()->after('ringcentral_session_id');
            $table->string('ringcentral_direction', 20)->nullable()->after('ringcentral_result');
            $table->timestamp('ringcentral_call_started_at')->nullable()->after('ringcentral_direction');
            $table->unsignedInteger('ringcentral_duration')->nullable()->after('ringcentral_call_started_at');
            $table->string('ringcentral_from_phone', 50)->nullable()->after('ringcentral_duration');
            $table->string('ringcentral_to_phone', 50)->nullable()->after('ringcentral_from_phone');
            $table->text('ringcentral_error')->nullable()->after('ringcentral_to_phone');
        });
    }

    public function down(): void
    {
        Schema::table('phone_clicks', function (Blueprint $table): void {
            $table->dropUnique(['ringcentral_call_id']);
            $table->dropIndex(['ringcentral_status']);
            $table->dropColumn([
                'ringcentral_status',
                'ringcentral_checked_at',
                'ringcentral_attempts',
                'ringcentral_call_id',
                'ringcentral_session_id',
                'ringcentral_result',
                'ringcentral_direction',
                'ringcentral_call_started_at',
                'ringcentral_duration',
                'ringcentral_from_phone',
                'ringcentral_to_phone',
                'ringcentral_error',
            ]);
        });
    }
};
