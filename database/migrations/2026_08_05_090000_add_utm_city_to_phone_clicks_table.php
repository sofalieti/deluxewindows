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
            $table->string('utm_city')->nullable()->after('utm_term');
            $table->string('utm_redirect')->nullable()->after('utm_city');
            $table->string('first_utm_city')->nullable()->after('first_utm_term');
        });
    }

    public function down(): void
    {
        Schema::table('phone_clicks', function (Blueprint $table): void {
            $table->dropColumn(['utm_city', 'utm_redirect', 'first_utm_city']);
        });
    }
};
