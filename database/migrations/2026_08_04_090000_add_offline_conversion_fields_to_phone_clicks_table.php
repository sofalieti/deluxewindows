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
            $table->timestamp('google_ads_conversion_sent_at')->nullable()->after('google_sheet_sent_by');
            $table->string('google_ads_conversion_error', 1000)->nullable()->after('google_ads_conversion_sent_at');
            $table->timestamp('bing_ads_conversion_sent_at')->nullable()->after('google_ads_conversion_error');
            $table->string('bing_ads_conversion_error', 1000)->nullable()->after('bing_ads_conversion_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('phone_clicks', function (Blueprint $table): void {
            $table->dropColumn([
                'google_ads_conversion_sent_at',
                'google_ads_conversion_error',
                'bing_ads_conversion_sent_at',
                'bing_ads_conversion_error',
            ]);
        });
    }
};
