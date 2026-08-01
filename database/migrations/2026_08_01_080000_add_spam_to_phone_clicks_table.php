<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phone_clicks', function (Blueprint $table): void {
            $table->boolean('is_spam')->default(false)->after('meta')->index();
            $table->timestamp('spam_marked_at')->nullable()->after('is_spam');
        });
    }

    public function down(): void
    {
        Schema::table('phone_clicks', function (Blueprint $table): void {
            $table->dropIndex(['is_spam']);
            $table->dropColumn(['is_spam', 'spam_marked_at']);
        });
    }
};
