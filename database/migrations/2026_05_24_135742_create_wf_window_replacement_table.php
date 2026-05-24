<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wf_window_replacement', function (Blueprint $table) {
            $table->id();
            $table->string('webflow_item_id')->unique();
            $table->string('webflow_cms_locale_id')->nullable();
            $table->timestamp('webflow_created_on')->nullable();
            $table->timestamp('webflow_updated_on')->nullable();
            $table->timestamp('webflow_published_on')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_draft')->default(false);
            $table->json('field_data')->nullable();
            $table->text('wf_city_name')->nullable();
            $table->text('wf_city_slug')->nullable();
            $table->text('wf_county')->nullable();
            $table->text('wf_meta_title')->nullable();
            $table->text('wf_meta_description')->nullable();
            $table->json('wf_hero_image')->nullable();
            $table->text('wf_hero_subheadline')->nullable();
            $table->text('wf_neighborhoods')->nullable();
            $table->longText('wf_city_paragraph_1')->nullable();
            $table->longText('wf_city_paragraph_2')->nullable();
            $table->text('wf_pricing_context')->nullable();
            $table->text('wf_faq_1_question')->nullable();
            $table->longText('wf_faq_1_answer')->nullable();
            $table->text('wf_faq_2_question')->nullable();
            $table->longText('wf_faq_2_answer')->nullable();
            $table->text('wf_faq_3_question')->nullable();
            $table->longText('wf_faq_3_answer')->nullable();
            $table->text('wf_faq_4_question')->nullable();
            $table->longText('wf_faq_4_answer')->nullable();
            $table->text('wf_faq_5_question')->nullable();
            $table->longText('wf_faq_5_answer')->nullable();
            $table->decimal('wf_review_count', 16, 4)->nullable();
            $table->json('wf_og_image')->nullable();
            $table->boolean('wf_is_priority')->nullable();
            $table->boolean('wf_is_published')->nullable();
            $table->decimal('wf_population', 16, 4)->nullable();
            $table->text('wf_schema_json')->nullable();
            $table->json('wf_nearby_cities')->nullable();
            $table->json('wf_county_page')->nullable();
            $table->json('wf_featured_brands')->nullable();
            $table->text('wf_faq_1_answer_plain_text')->nullable();
            $table->text('wf_faq_2_answer_plain_text')->nullable();
            $table->text('wf_faq_3_answer_plain_text')->nullable();
            $table->text('wf_faq_4_answer_plain_text')->nullable();
            $table->text('wf_faq_5_answer_plain_text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wf_window_replacement');
    }
};
