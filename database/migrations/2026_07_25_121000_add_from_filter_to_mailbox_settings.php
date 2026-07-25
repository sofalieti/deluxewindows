<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mailbox_settings', function (Blueprint $table): void {
            $table->string('from_filter')->default('notify.deluxewindows.com')->after('subject_filter');
        });

        if (Schema::hasTable('mailbox_settings')) {
            DB::table('mailbox_settings')->update([
                'from_filter' => 'notify.deluxewindows.com',
            ]);
        }

        // Drop stale settings cache so the new column is loaded.
        try {
            \Illuminate\Support\Facades\Cache::forget('mailbox.settings.default');
        } catch (\Throwable) {
            //
        }
    }

    public function down(): void
    {
        Schema::table('mailbox_settings', function (Blueprint $table): void {
            $table->dropColumn('from_filter');
        });
    }
};
