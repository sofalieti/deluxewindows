<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wf_collections_tabs', function (Blueprint $table) {
            $table->id();
            $table->string('webflow_item_id')->unique();
            $table->string('webflow_cms_locale_id')->nullable();
            $table->timestamp('webflow_created_on')->nullable();
            $table->timestamp('webflow_updated_on')->nullable();
            $table->timestamp('webflow_published_on')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_draft')->default(false);
            $table->json('field_data')->nullable();
            $table->text('wf_description')->nullable();
            $table->json('wf_picture')->nullable();
            $table->text('wf_category')->nullable();
            $table->text('wf_subcategory')->nullable();
            $table->json('wf_parent_collection')->nullable();
            $table->json('wf_parent_brand')->nullable();
            $table->json('wf_parent_window_styles')->nullable();
            $table->text('wf_color')->nullable();
            $table->json('wf_materials')->nullable();
            $table->json('wf_collections_new_template')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wf_collections_tabs');
    }
};
