<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('scope', 32)->unique();
            $table->boolean('enabled')->default(false);
            $table->timestamps();
        });

        Schema::create('site_visits', function (Blueprint $table): void {
            $table->id();
            $table->string('page_url', 1000)->nullable();
            $table->string('landing_page', 1000)->nullable();
            $table->string('first_landing_page', 1000)->nullable();
            $table->string('referrer', 1000)->nullable();
            $table->string('first_referrer', 1000)->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_city')->nullable();
            $table->string('utm_redirect')->nullable();
            $table->string('first_utm_source')->nullable();
            $table->string('first_utm_medium')->nullable();
            $table->string('first_utm_campaign')->nullable();
            $table->string('first_utm_content')->nullable();
            $table->string('first_utm_term')->nullable();
            $table->string('first_utm_city')->nullable();
            $table->string('matchtype')->nullable();
            $table->string('device')->nullable();
            $table->string('creative')->nullable();
            $table->string('gclid')->nullable();
            $table->string('fbclid')->nullable();
            $table->string('msclkid')->nullable();
            $table->string('first_gclid')->nullable();
            $table->string('first_fbclid')->nullable();
            $table->string('first_msclkid')->nullable();
            $table->string('geo_location')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('traffic_source', 64)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('utm_city');
            $table->index('traffic_source');
            $table->index('utm_source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_visits');
        Schema::dropIfExists('visits_settings');
    }
};
