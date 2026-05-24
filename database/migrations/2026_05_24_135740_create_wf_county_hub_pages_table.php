<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wf_county_hub_pages', function (Blueprint $table) {
            $table->id();
            $table->string('webflow_item_id')->unique();
            $table->string('webflow_cms_locale_id')->nullable();
            $table->timestamp('webflow_created_on')->nullable();
            $table->timestamp('webflow_updated_on')->nullable();
            $table->timestamp('webflow_published_on')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_draft')->default(false);
            $table->json('field_data')->nullable();
            $table->text('wf_county_name')->nullable();
            $table->text('wf_county_slug')->nullable();
            $table->text('wf_meta_title')->nullable();
            $table->text('wf_meta_description')->nullable();
            $table->longText('wf_county_intro')->nullable();
            $table->json('wf_hero_image')->nullable();
            $table->json('wf_cities_in_county')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wf_county_hub_pages');
    }
};
