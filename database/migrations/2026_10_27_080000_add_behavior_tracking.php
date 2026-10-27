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
            $table->string('visitor_id', 36)->nullable()->index();
            $table->string('session_id', 36)->nullable()->index();
            $table->unsignedInteger('pageviews')->default(0);
            $table->unsignedInteger('engaged_seconds')->default(0);
            $table->unsignedTinyInteger('max_scroll')->default(0);
            $table->unsignedInteger('events_count')->default(0);
            $table->unsignedInteger('rage_clicks')->default(0);
            $table->timestamp('last_seen_at')->nullable();
            $table->boolean('is_bot')->default(false);
        });

        Schema::table('leads', function (Blueprint $table): void {
            $table->string('visitor_id', 36)->nullable()->index();
            $table->string('session_id', 36)->nullable()->index();
        });

        Schema::table('phone_clicks', function (Blueprint $table): void {
            $table->string('visitor_id', 36)->nullable()->index();
            $table->string('session_id', 36)->nullable()->index();
        });

        Schema::create('site_events', function (Blueprint $table): void {
            $table->id();
            $table->string('session_id', 36);
            $table->string('visitor_id', 36)->nullable();
            $table->string('type', 32);
            $table->string('path', 500)->nullable();
            $table->string('target', 255)->nullable();
            $table->unsignedInteger('value')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['session_id', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_events');

        Schema::table('phone_clicks', function (Blueprint $table): void {
            $table->dropIndex(['visitor_id']);
            $table->dropIndex(['session_id']);
            $table->dropColumn(['visitor_id', 'session_id']);
        });

        Schema::table('leads', function (Blueprint $table): void {
            $table->dropIndex(['visitor_id']);
            $table->dropIndex(['session_id']);
            $table->dropColumn(['visitor_id', 'session_id']);
        });

        Schema::table('site_visits', function (Blueprint $table): void {
            $table->dropIndex(['visitor_id']);
            $table->dropIndex(['session_id']);
            $table->dropColumn([
                'visitor_id',
                'session_id',
                'pageviews',
                'engaged_seconds',
                'max_scroll',
                'events_count',
                'rage_clicks',
                'last_seen_at',
                'is_bot',
            ]);
        });
    }
};
