<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('promotion_controls')) {
            return;
        }

        Schema::table('promotion_controls', function (Blueprint $table): void {
            if (! Schema::hasColumn('promotion_controls', 'global_promotion_name')) {
                $table->string('global_promotion_name')->nullable()->after('scope');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('promotion_controls')) {
            return;
        }

        Schema::table('promotion_controls', function (Blueprint $table): void {
            if (Schema::hasColumn('promotion_controls', 'global_promotion_name')) {
                $table->dropColumn('global_promotion_name');
            }
        });
    }
};

