<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['site_visits', 'leads', 'phone_clicks'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->unsignedSmallInteger('screen_width')->nullable()->after('user_agent');
                $table->unsignedSmallInteger('screen_height')->nullable()->after('screen_width');
            });
        }
    }

    public function down(): void
    {
        foreach (['site_visits', 'leads', 'phone_clicks'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->dropColumn(['screen_width', 'screen_height']);
            });
        }
    }
};
