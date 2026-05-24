<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wf_collection', function (Blueprint $table) {
            $table->id();
            $table->string('webflow_item_id')->unique();
            $table->string('webflow_cms_locale_id')->nullable();
            $table->timestamp('webflow_created_on')->nullable();
            $table->timestamp('webflow_updated_on')->nullable();
            $table->timestamp('webflow_published_on')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_draft')->default(false);
            $table->json('field_data')->nullable();
            $table->json('wf_property_type_icon')->nullable();
            $table->text('wf_property_type_description')->nullable();
            $table->json('wf_featured_image')->nullable();
            $table->longText('wf_description')->nullable();
            $table->text('wf_price_category')->nullable();
            $table->text('wf_video')->nullable();
            $table->text('wf_explore_brand_style')->nullable();
            $table->json('wf_brand_material_style')->nullable();
            $table->json('wf_parent_brand')->nullable();
            $table->longText('wf_about_tab')->nullable();
            $table->json('wf_collections_tabs_details')->nullable();
            $table->json('wf_inspiration_photos')->nullable();
            $table->json('wf_other_collections')->nullable();
            $table->json('wf_materials')->nullable();
            $table->text('wf_seo_title')->nullable();
            $table->text('wf_seo_description')->nullable();
            $table->text('wf_opengraph_title')->nullable();
            $table->text('wf_opengraph_description')->nullable();
            $table->text('wf_opengraph_image')->nullable();
            $table->text('wf_twitter_card')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wf_collection');
    }
};
