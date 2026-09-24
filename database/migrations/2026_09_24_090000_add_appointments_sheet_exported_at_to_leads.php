<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            if (! Schema::hasColumn('leads', 'appointments_sheet_exported_at')) {
                $table->timestamp('appointments_sheet_exported_at')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            if (Schema::hasColumn('leads', 'appointments_sheet_exported_at')) {
                $table->dropColumn('appointments_sheet_exported_at');
            }
        });
    }
};
