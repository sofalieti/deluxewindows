<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('phone_clicks')) {
            return;
        }

        Schema::table('phone_clicks', function (Blueprint $table): void {
            if (! Schema::hasColumn('phone_clicks', 'google_ads_sheet_exported_at')) {
                $table->timestamp('google_ads_sheet_exported_at')->nullable()->after('google_ads_conversion_error');
            }
            if (! Schema::hasColumn('phone_clicks', 'google_ads_sheet_url')) {
                $table->string('google_ads_sheet_url', 500)->nullable()->after('google_ads_sheet_exported_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('phone_clicks')) {
            return;
        }

        Schema::table('phone_clicks', function (Blueprint $table): void {
            if (Schema::hasColumn('phone_clicks', 'google_ads_sheet_url')) {
                $table->dropColumn('google_ads_sheet_url');
            }
            if (Schema::hasColumn('phone_clicks', 'google_ads_sheet_exported_at')) {
                $table->dropColumn('google_ads_sheet_exported_at');
            }
        });
    }
};
