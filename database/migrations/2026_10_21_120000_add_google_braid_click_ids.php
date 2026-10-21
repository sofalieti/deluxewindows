<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_visits', function (Blueprint $table): void {
            $table->string('gbraid')->nullable()->after('gclid');
            $table->string('wbraid')->nullable()->after('gbraid');
            $table->string('first_gbraid')->nullable()->after('first_gclid');
            $table->string('first_wbraid')->nullable()->after('first_gbraid');
        });

        Schema::table('phone_clicks', function (Blueprint $table): void {
            $table->string('gbraid')->nullable()->after('gclid');
            $table->string('wbraid')->nullable()->after('gbraid');
            $table->string('first_gbraid')->nullable()->after('first_gclid');
            $table->string('first_wbraid')->nullable()->after('first_gbraid');
        });
    }

    public function down(): void
    {
        Schema::table('site_visits', function (Blueprint $table): void {
            $table->dropColumn(['gbraid', 'wbraid', 'first_gbraid', 'first_wbraid']);
        });

        Schema::table('phone_clicks', function (Blueprint $table): void {
            $table->dropColumn(['gbraid', 'wbraid', 'first_gbraid', 'first_wbraid']);
        });
    }
};
