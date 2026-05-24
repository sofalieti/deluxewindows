<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wf_product', function (Blueprint $table) {
            $table->id();
            $table->string('webflow_item_id')->unique();
            $table->string('webflow_cms_locale_id')->nullable();
            $table->timestamp('webflow_created_on')->nullable();
            $table->timestamp('webflow_updated_on')->nullable();
            $table->timestamp('webflow_published_on')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_draft')->default(false);
            $table->json('field_data')->nullable();
            $table->text('wf_sku_properties')->nullable();
            $table->json('wf_category')->nullable();
            $table->text('wf_description')->nullable();
            $table->boolean('wf_shippable')->nullable();
            $table->text('wf_tax_category')->nullable();
            $table->json('wf_default_sku')->nullable();
            $table->json('wf_ec_product_type')->nullable();
            $table->longText('wf_product_description')->nullable();
            $table->text('wf_product_short_description_page_2')->nullable();
            $table->text('wf_product_excerpt')->nullable();
            $table->text('wf_product_display_price')->nullable();
            $table->text('wf_product_feature_1')->nullable();
            $table->text('wf_product_feature_2')->nullable();
            $table->text('wf_product_feature_3')->nullable();
            $table->text('wf_product_feature_4')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wf_product');
    }
};
