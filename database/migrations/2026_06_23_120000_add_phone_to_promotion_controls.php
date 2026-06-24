<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotion_controls', function (Blueprint $table): void {
            $table->string('phone_display')->nullable()->default('(650) 461-4446')->after('global_end_date');
            $table->string('phone_tel')->nullable()->default('+16504614446')->after('phone_display');
        });
    }

    public function down(): void
    {
        Schema::table('promotion_controls', function (Blueprint $table): void {
            $table->dropColumn(['phone_display', 'phone_tel']);
        });
    }
};
