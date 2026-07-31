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
            $table->json('ringcentral_extra_phones')->nullable()->after('phone_tel');
        });
    }

    public function down(): void
    {
        Schema::table('promotion_controls', function (Blueprint $table): void {
            $table->dropColumn('ringcentral_extra_phones');
        });
    }
};
