<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wf_doors')) {
            return;
        }

        Schema::table('wf_doors', function (Blueprint $table): void {
            if (! Schema::hasColumn('wf_doors', 'wf_custom_hero_image')) {
                $table->json('wf_custom_hero_image')->nullable()->after('wf_door_discount');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('wf_doors')) {
            return;
        }

        Schema::table('wf_doors', function (Blueprint $table): void {
            if (Schema::hasColumn('wf_doors', 'wf_custom_hero_image')) {
                $table->dropColumn('wf_custom_hero_image');
            }
        });
    }
};
