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
            $table->timestamp('google_sheet_sent_at')->nullable()->after('meta')->index();
            $table->foreignId('google_sheet_sent_by')
                ->nullable()
                ->after('google_sheet_sent_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('phone_clicks', function (Blueprint $table): void {
            $table->dropForeign(['google_sheet_sent_by']);
            $table->dropIndex(['google_sheet_sent_at']);
            $table->dropColumn([
                'google_sheet_sent_at',
                'google_sheet_sent_by',
            ]);
        });
    }
};
