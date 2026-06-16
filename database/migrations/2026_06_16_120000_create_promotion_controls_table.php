<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_controls', function (Blueprint $table): void {
            $table->id();
            $table->string('scope')->unique()->default('default');
            $table->string('global_promotion_name')->nullable();
            $table->unsignedTinyInteger('global_discount_percent')->default(40);
            $table->date('global_end_date')->nullable();
            $table->json('window_type_prices')->nullable();
            $table->json('series_prices')->nullable();
            $table->json('brand_prices')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_controls');
    }
};

