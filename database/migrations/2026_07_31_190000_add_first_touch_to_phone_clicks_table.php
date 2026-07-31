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
            $table->string('first_landing_page', 1000)->nullable()->after('landing_page');
            $table->string('first_referrer', 1000)->nullable()->after('referrer');
            $table->string('first_utm_source')->nullable()->after('utm_term');
            $table->string('first_utm_medium')->nullable()->after('first_utm_source');
            $table->string('first_utm_campaign')->nullable()->after('first_utm_medium');
            $table->string('first_utm_content')->nullable()->after('first_utm_campaign');
            $table->string('first_utm_term')->nullable()->after('first_utm_content');
            $table->string('first_gclid')->nullable()->after('msclkid');
            $table->string('first_fbclid')->nullable()->after('first_gclid');
            $table->string('first_msclkid')->nullable()->after('first_fbclid');

            $table->index('first_utm_source');
        });
    }

    public function down(): void
    {
        Schema::table('phone_clicks', function (Blueprint $table): void {
            $table->dropIndex(['first_utm_source']);
            $table->dropColumn([
                'first_landing_page',
                'first_referrer',
                'first_utm_source',
                'first_utm_medium',
                'first_utm_campaign',
                'first_utm_content',
                'first_utm_term',
                'first_gclid',
                'first_fbclid',
                'first_msclkid',
            ]);
        });
    }
};
