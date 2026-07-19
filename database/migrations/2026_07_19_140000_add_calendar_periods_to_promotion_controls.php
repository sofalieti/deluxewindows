<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotion_controls', function (Blueprint $table) {
            if (! Schema::hasColumn('promotion_controls', 'calendar_periods')) {
                $table->json('calendar_periods')->nullable()->after('door_prices');
            }
        });
    }

    public function down(): void
    {
        Schema::table('promotion_controls', function (Blueprint $table) {
            if (Schema::hasColumn('promotion_controls', 'calendar_periods')) {
                $table->dropColumn('calendar_periods');
            }
        });
    }
};
