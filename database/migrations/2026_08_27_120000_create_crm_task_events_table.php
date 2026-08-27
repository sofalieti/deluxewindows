<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_task_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_task_id')->constrained('crm_tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 32);
            $table->string('comment', 1000)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['crm_task_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_task_events');
    }
};
