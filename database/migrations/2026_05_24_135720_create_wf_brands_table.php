<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wf_brands', function (Blueprint $table) {
            $table->id();
            $table->string('webflow_item_id')->unique();
            $table->string('webflow_cms_locale_id')->nullable();
            $table->timestamp('webflow_created_on')->nullable();
            $table->timestamp('webflow_updated_on')->nullable();
            $table->timestamp('webflow_published_on')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_draft')->default(false);
            $table->json('field_data')->nullable();
            $table->json('wf_agent_avatar_photo')->nullable();
            $table->longText('wf_agent_about')->nullable();
            $table->longText('wf_agent_experience')->nullable();
            $table->text('wf_agent_excerpt')->nullable();
            $table->text('wf_agent_job_title')->nullable();
            $table->text('wf_agent_username')->nullable();
            $table->json('wf_featured_image')->nullable();
            $table->json('wf_doors_type_marvin')->nullable();
            $table->json('wf_window_types')->nullable();
            $table->text('wf_windows_titles')->nullable();
            $table->text('wf_doors_title')->nullable();
            $table->json('wf_brand_logo')->nullable();
            $table->json('wf_logo_svg')->nullable();
            $table->decimal('wf_order', 16, 4)->nullable();
            $table->json('wf_materials')->nullable();
            $table->boolean('wf_new_template')->nullable();
            $table->boolean('wf_animation_off')->nullable();
            $table->boolean('wf_grid_styles')->nullable();
            $table->boolean('wf_tinted_glass_off')->nullable();
            $table->boolean('wf_standard_glass_is_big')->nullable();
            $table->boolean('wf_obscure_glass_if_off')->nullable();
            $table->boolean('wf_energy_glass_is_off')->nullable();
            $table->boolean('wf_new_construction_replacement_categories')->nullable();
            $table->text('wf_brand_feature_description_1')->nullable();
            $table->text('wf_brand_feature_description_2')->nullable();
            $table->text('wf_brand_feature_description_3')->nullable();
            $table->text('wf_brand_feature_description_4')->nullable();
            $table->text('wf_price_range')->nullable();
            $table->boolean('wf_field')->nullable();
            $table->boolean('wf_field_2')->nullable();
            $table->boolean('wf_field_3')->nullable();
            $table->boolean('wf_field_4')->nullable();
            $table->boolean('wf_field_5')->nullable();
            $table->json('wf_windowmaintype')->nullable();
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
        Schema::dropIfExists('wf_brands');
    }
};
