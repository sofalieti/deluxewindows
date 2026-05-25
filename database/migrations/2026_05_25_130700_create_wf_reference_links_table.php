<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wf_reference_links', function (Blueprint $table) {
            $table->id();
            $table->string('source_collection_slug');
            $table->unsignedBigInteger('source_id');
            $table->string('source_webflow_item_id')->nullable();
            $table->string('field_slug');
            $table->string('relation_type');
            $table->string('target_collection_slug');
            $table->string('target_webflow_item_id');
            $table->unsignedBigInteger('target_id')->nullable();
            $table->timestamps();

            $table->unique(
                ['source_collection_slug', 'source_id', 'field_slug', 'target_webflow_item_id'],
                'wf_ref_links_unique'
            );

            $table->index(['source_collection_slug', 'source_id'], 'wf_ref_links_source_idx');
            $table->index(['target_collection_slug', 'target_webflow_item_id'], 'wf_ref_links_target_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wf_reference_links');
    }
};

