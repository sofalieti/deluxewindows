<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotion_controls', function (Blueprint $table) {
            if (! Schema::hasColumn('promotion_controls', 'door_prices')) {
                $table->json('door_prices')->nullable()->after('brand_prices');
            }
        });
    }

    public function down(): void
    {
        Schema::table('promotion_controls', function (Blueprint $table) {
            if (Schema::hasColumn('promotion_controls', 'door_prices')) {
                $table->dropColumn('door_prices');
            }
        });
    }
};
