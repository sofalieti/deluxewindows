<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_clicks', function (Blueprint $table): void {
            $table->id();
            $table->string('phone', 50)->nullable();
            $table->string('page_url', 1000)->nullable();
            $table->string('landing_page', 1000)->nullable();
            $table->string('referrer', 1000)->nullable();
            $table->string('source_label', 255)->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('matchtype')->nullable();
            $table->string('device')->nullable();
            $table->string('creative')->nullable();
            $table->string('gclid')->nullable();
            $table->string('fbclid')->nullable();
            $table->string('msclkid')->nullable();
            $table->string('geo_location')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('utm_source');
            $table->index('gclid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_clicks');
    }
};
