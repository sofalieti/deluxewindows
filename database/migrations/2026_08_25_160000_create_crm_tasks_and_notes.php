<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_tasks', function (Blueprint $table): void {
            $table->id();
            $table->nullableMorphs('subject');
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type', 32)->default('other')->index();
            $table->string('status', 32)->default('open');
            $table->string('priority', 16)->default('normal');
            $table->timestamp('due_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('result', 1000)->nullable();
            $table->timestamps();

            $table->index(['assigned_to', 'status', 'due_at']);
            $table->index(['contact_id']);
            $table->index(['status', 'due_at']);
        });

        Schema::create('crm_notes', function (Blueprint $table): void {
            $table->id();
            $table->morphs('subject');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_notes');
        Schema::dropIfExists('crm_tasks');
    }
};
