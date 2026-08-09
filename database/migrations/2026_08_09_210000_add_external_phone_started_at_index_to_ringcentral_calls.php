<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ringcentral_calls')) {
            return;
        }

        Schema::table('ringcentral_calls', function (Blueprint $table): void {
            $table->index(['external_phone', 'started_at'], 'ringcentral_calls_external_phone_started_at_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ringcentral_calls')) {
            return;
        }

        Schema::table('ringcentral_calls', function (Blueprint $table): void {
            $table->dropIndex('ringcentral_calls_external_phone_started_at_index');
        });
    }
};
