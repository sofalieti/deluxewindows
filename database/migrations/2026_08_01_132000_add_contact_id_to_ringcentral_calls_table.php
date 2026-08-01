<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ringcentral_calls', 'contact_id')) {
            Schema::table('ringcentral_calls', function (Blueprint $table): void {
                $table->foreignId('contact_id')
                    ->nullable()
                    ->after('external_phone')
                    ->constrained('contacts')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ringcentral_calls', 'contact_id')) {
            Schema::table('ringcentral_calls', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('contact_id');
            });
        }
    }
};
