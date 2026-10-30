<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ringcentral_calls') || Schema::hasColumn('ringcentral_calls', 'lead_id')) {
            return;
        }

        Schema::table('ringcentral_calls', function (Blueprint $table): void {
            $table->foreignId('lead_id')
                ->nullable()
                ->after('contact_id')
                ->constrained('leads')
                ->nullOnDelete();
        });

        app(\App\Services\RingCentralContactBinder::class)->backfillLinks();
    }

    public function down(): void
    {
        if (! Schema::hasTable('ringcentral_calls') || ! Schema::hasColumn('ringcentral_calls', 'lead_id')) {
            return;
        }

        Schema::table('ringcentral_calls', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('lead_id');
        });
    }
};
