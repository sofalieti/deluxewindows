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
            if (! Schema::hasColumn('ringcentral_calls', 'transcript_status')) {
                $table->string('transcript_status', 32)->nullable()->after('recording_id')->index();
            }
            if (! Schema::hasColumn('ringcentral_calls', 'transcript_queued_at')) {
                $table->timestamp('transcript_queued_at')->nullable()->after('transcript_status');
            }
            if (! Schema::hasColumn('ringcentral_calls', 'transcript_processed_at')) {
                $table->timestamp('transcript_processed_at')->nullable()->after('transcript_queued_at');
            }
            if (! Schema::hasColumn('ringcentral_calls', 'transcript')) {
                $table->longText('transcript')->nullable()->after('transcript_processed_at');
            }
            if (! Schema::hasColumn('ringcentral_calls', 'transcript_summary')) {
                $table->json('transcript_summary')->nullable()->after('transcript');
            }
            if (! Schema::hasColumn('ringcentral_calls', 'transcript_error')) {
                $table->text('transcript_error')->nullable()->after('transcript_summary');
            }
            if (! Schema::hasColumn('ringcentral_calls', 'transcript_meta')) {
                $table->json('transcript_meta')->nullable()->after('transcript_error');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ringcentral_calls')) {
            return;
        }

        Schema::table('ringcentral_calls', function (Blueprint $table): void {
            foreach ([
                'transcript_status',
                'transcript_queued_at',
                'transcript_processed_at',
                'transcript',
                'transcript_summary',
                'transcript_error',
                'transcript_meta',
            ] as $column) {
                if (Schema::hasColumn('ringcentral_calls', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
