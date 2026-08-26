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
        Schema::create('contact_emails', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->string('email');
            $table->string('normalized_email')->index();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['contact_id', 'normalized_email'], 'contact_emails_contact_normalized_unique');
        });

        if (! Schema::hasTable('contacts')) {
            return;
        }

        $now = now();
        Contact::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($contacts) use ($now): void {
                $rows = [];
                foreach ($contacts as $contact) {
                    $normalized = Contact::normalizeEmail($contact->email);
                    if ($normalized === null) {
                        continue;
                    }
                    $rows[] = [
                        'contact_id' => $contact->id,
                        'email' => trim((string) $contact->email),
                        'normalized_email' => $normalized,
                        'is_primary' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                if ($rows !== []) {
                    DB::table('contact_emails')->insertOrIgnore($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_emails');
    }
};
