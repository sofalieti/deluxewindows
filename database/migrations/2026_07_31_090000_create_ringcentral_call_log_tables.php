<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ringcentral_calls', function (Blueprint $table): void {
            $table->id();
            $table->string('ringcentral_call_id')->unique();
            $table->string('session_id')->nullable()->index();
            $table->string('telephony_session_id')->nullable()->index();
            $table->string('direction', 32)->index();
            $table->string('action')->nullable();
            $table->string('result')->nullable();
            $table->timestamp('started_at')->index();
            $table->unsignedInteger('duration')->default(0);
            $table->string('business_phone', 50)->index();
            $table->string('from_phone', 50)->nullable();
            $table->string('from_name')->nullable();
            $table->string('to_phone', 50)->nullable();
            $table->string('to_name')->nullable();
            $table->string('external_phone', 50)->nullable()->index();
            $table->json('raw')->nullable();
            $table->timestamp('synced_at');
            $table->timestamps();
        });

        Schema::create('ringcentral_call_sync_states', function (Blueprint $table): void {
            $table->id();
            $table->string('business_phone', 50)->unique();
            $table->timestamp('started_at');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ringcentral_excluded_numbers', function (Blueprint $table): void {
            $table->id();
            $table->string('phone', 50)->unique();
            $table->timestamp('excluded_at');
            $table->foreignId('excluded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('restored_at')->nullable()->index();
            $table->foreignId('restored_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['restored_at', 'excluded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ringcentral_excluded_numbers');
        Schema::dropIfExists('ringcentral_call_sync_states');
        Schema::dropIfExists('ringcentral_calls');
    }
};
