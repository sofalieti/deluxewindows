<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ringcentral_calls', function (Blueprint $table): void {
            $table->string('handling_status', 32)->default('new')->after('contact_id')->index();
            $table->timestamp('handled_at')->nullable()->after('handling_status');
            $table->foreignId('handled_by')->nullable()->after('handled_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ringcentral_calls', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('handled_by');
            $table->dropColumn(['handling_status', 'handled_at']);
        });
    }
};
