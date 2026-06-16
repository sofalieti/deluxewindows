<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wf_windows', function (Blueprint $table) {
            $table->id();
            $table->string('webflow_item_id')->unique();
            $table->string('webflow_cms_locale_id')->nullable();
            $table->timestamp('webflow_created_on')->nullable();
            $table->timestamp('webflow_updated_on')->nullable();
            $table->timestamp('webflow_published_on')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_draft')->default(false);
            $table->json('field_data')->nullable();
            $table->longText('wf_discounttext')->nullable();
            $table->json('wf_custom_hero_image')->nullable();
            $table->json('wf_property_listing_featured_image')->nullable();
            $table->json('wf_property_listing_featured_images')->nullable();
            $table->json('wf_property_listing_thumbnail_image_v1')->nullable();
            $table->json('wf_property_listing_thumbnail_image_v2')->nullable();
            $table->json('wf_property_listing_thumbnail_image_v3')->nullable();
            $table->longText('wf_property_listing_about')->nullable();
            $table->text('wf_property_listing_summary')->nullable();
            $table->text('wf_property_listing_excerpt')->nullable();
            $table->boolean('wf_property_listing_property_featured')->nullable();
            $table->text('wf_property_listing_address')->nullable();
            $table->text('wf_property_listing_sqf')->nullable();
            $table->decimal('wf_property_listing_number_of_bathrooms', 16, 4)->nullable();
            $table->decimal('wf_property_listing_number_of_bedrooms', 16, 4)->nullable();
            $table->decimal('wf_property_listing_number_of_parking_spots', 16, 4)->nullable();
            $table->text('wf_property_listing_display_price')->nullable();
            $table->json('wf_property_listing_type')->nullable();
            $table->json('wf_property_listing_agent')->nullable();
            $table->json('wf_brands')->nullable();
            $table->text('wf_title_for_brands')->nullable();
            $table->json('wf_brands_types')->nullable();
            $table->text('wf_parent_collection')->nullable();
            $table->json('wf_collections')->nullable();
            $table->text('wf_category_name')->nullable();
            $table->text('wf_short_title')->nullable();
            $table->longText('wf_warrantytext')->nullable();
            $table->text('wf_warrantyline')->nullable();
            $table->text('wf_seo_title')->nullable();
            $table->text('wf_seo_description')->nullable();
            $table->text('wf_opengraph_title')->nullable();
            $table->text('wf_opengraph_description')->nullable();
            $table->text('wf_opengraph_image')->nullable();
            $table->text('wf_twitter_card')->nullable();
            $table->boolean('wf_hide')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wf_windows');
    }
};
