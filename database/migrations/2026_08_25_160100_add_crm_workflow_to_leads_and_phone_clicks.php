<?php

declare(strict_types=1);

use App\Models\Contact;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->foreignId('assigned_to')->nullable()->after('referral_partner_id')->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable()->after('assigned_to');
        });

        Schema::table('phone_clicks', function (Blueprint $table): void {
            $table->string('handling_status', 32)->default('new')->after('is_spam')->index();
            $table->timestamp('handled_at')->nullable()->after('handling_status');
            $table->foreignId('handled_by')->nullable()->after('handled_at')->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->after('handled_by')->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable()->after('assigned_to');
            $table->foreignId('contact_id')->nullable()->after('assigned_at')->constrained('contacts')->nullOnDelete();
            $table->string('normalized_phone', 32)->nullable()->after('phone')->index();
        });

        DB::table('phone_clicks')
            ->select(['id', 'phone', 'ringcentral_from_phone', 'ringcentral_to_phone', 'ringcentral_direction', 'ringcentral_status'])
            ->orderBy('id')
            ->chunkById(250, function ($clicks): void {
                foreach ($clicks as $click) {
                    $client = null;
                    if ((string) ($click->ringcentral_status ?? '') === 'found') {
                        $direction = ucfirst(strtolower(trim((string) ($click->ringcentral_direction ?? ''))));
                        $from = trim((string) ($click->ringcentral_from_phone ?? ''));
                        $to = trim((string) ($click->ringcentral_to_phone ?? ''));
                        $client = $direction === 'Outbound' ? $to : $from;
                        if ($client === '') {
                            $client = $from !== '' ? $from : $to;
                        }
                    }

                    DB::table('phone_clicks')->where('id', $click->id)->update([
                        'normalized_phone' => Contact::normalizePhone($client ?: $click->phone),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropColumn('assigned_at');
        });

        Schema::table('phone_clicks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('handled_by');
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropConstrainedForeignId('contact_id');
            $table->dropColumn([
                'handling_status',
                'handled_at',
                'assigned_at',
                'normalized_phone',
            ]);
        });
    }
};
