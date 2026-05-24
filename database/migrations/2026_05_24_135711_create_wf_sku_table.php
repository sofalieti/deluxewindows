<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wf_sku', function (Blueprint $table) {
            $table->id();
            $table->string('webflow_item_id')->unique();
            $table->string('webflow_cms_locale_id')->nullable();
            $table->timestamp('webflow_created_on')->nullable();
            $table->timestamp('webflow_updated_on')->nullable();
            $table->timestamp('webflow_published_on')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_draft')->default(false);
            $table->json('field_data')->nullable();
            $table->text('wf_sku_values')->nullable();
            $table->json('wf_product')->nullable();
            $table->json('wf_main_image')->nullable();
            $table->json('wf_more_images')->nullable();
            $table->text('wf_price')->nullable();
            $table->text('wf_compare_at_price')->nullable();
            $table->text('wf_download_files')->nullable();
            $table->text('wf_ec_sku_subscription_plan')->nullable();
            $table->decimal('wf_width', 16, 4)->nullable();
            $table->decimal('wf_length', 16, 4)->nullable();
            $table->decimal('wf_height', 16, 4)->nullable();
            $table->decimal('wf_weight', 16, 4)->nullable();
            $table->text('wf_sku')->nullable();
            $table->text('wf_ec_sku_billing_method')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wf_sku');
    }
};
