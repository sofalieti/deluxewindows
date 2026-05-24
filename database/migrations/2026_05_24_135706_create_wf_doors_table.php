<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wf_doors', function (Blueprint $table) {
            $table->id();
            $table->string('webflow_item_id')->unique();
            $table->string('webflow_cms_locale_id')->nullable();
            $table->timestamp('webflow_created_on')->nullable();
            $table->timestamp('webflow_updated_on')->nullable();
            $table->timestamp('webflow_published_on')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_draft')->default(false);
            $table->json('field_data')->nullable();
            $table->longText('wf_door_discount')->nullable();
            $table->json('wf_blog_post_featured_image')->nullable();
            $table->json('wf_blog_post_thumbnail_image_v1')->nullable();
            $table->json('wf_blog_post_thumbnail_image_v2')->nullable();
            $table->json('wf_blog_post_thumbnail_image_v3')->nullable();
            $table->json('wf_gallery')->nullable();
            $table->longText('wf_blog_post_rich_text')->nullable();
            $table->text('wf_blog_post_summary')->nullable();
            $table->text('wf_blog_post_excerpt')->nullable();
            $table->boolean('wf_featured')->nullable();
            $table->json('wf_blog_post_category')->nullable();
            $table->json('wf_blog_post_author')->nullable();
            $table->text('wf_description')->nullable();
            $table->json('wf_doors_brands')->nullable();
            $table->text('wf_brands_title')->nullable();
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
        Schema::dropIfExists('wf_doors');
    }
};
