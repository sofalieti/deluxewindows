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
        Schema::create('contacts', function (Blueprint $table): void {
            $table->id();
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('city', 100)->nullable();
            $table->text('address')->nullable();
            $table->text('additional_information')->nullable();
            $table->string('normalized_email')->nullable()->index();
            $table->string('normalized_phone', 32)->nullable()->index();
            $table->foreignId('source_lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('leads', function (Blueprint $table): void {
            $table->foreignId('contact_id')->nullable()->after('id')->constrained('contacts')->nullOnDelete();
            $table->string('normalized_email')->nullable()->after('email')->index();
            $table->string('normalized_phone', 32)->nullable()->after('phone')->index();
        });

        DB::table('leads')
            ->select(['id', 'email', 'phone'])
            ->orderBy('id')
            ->chunkById(250, function ($leads): void {
                foreach ($leads as $lead) {
                    DB::table('leads')->where('id', $lead->id)->update([
                        'normalized_email' => $this->normalizeEmail($lead->email),
                        'normalized_phone' => $this->normalizePhone($lead->phone),
                    ]);
                }
            });

        $this->grantPermissions('roles');
        $this->grantPermissions('users');
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('contact_id');
            $table->dropColumn(['normalized_email', 'normalized_phone']);
        });

        Schema::dropIfExists('contacts');
    }

    private function normalizeEmail(mixed $email): ?string
    {
        $value = strtolower(trim((string) $email));

        return $value !== '' ? $value : null;
    }

    private function normalizePhone(mixed $phone): ?string
    {
        $value = preg_replace('/\D+/', '', (string) $phone) ?? '';

        return $value !== '' ? $value : null;
    }

    private function grantPermissions(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'permissions')) {
            return;
        }

        DB::table($table)->orderBy('id')->each(function (object $row) use ($table): void {
            $permissions = json_decode((string) ($row->permissions ?? ''), true);
            if (! is_array($permissions)) {
                $permissions = [];
            }

            $permissions['platform.contacts'] = true;

            DB::table($table)->where('id', $row->id)->update([
                'permissions' => json_encode($permissions),
            ]);
        });
    }
};
