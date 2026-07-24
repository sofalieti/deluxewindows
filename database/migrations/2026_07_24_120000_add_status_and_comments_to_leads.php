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
        Schema::table('leads', function (Blueprint $table): void {
            $table->string('status', 32)->default('new')->after('meta');
            $table->index('status');
        });

        Schema::create('lead_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['lead_id', 'created_at']);
        });

        // Keep existing admin roles/users able to open newly gated screens.
        $newPermissions = [
            'platform.leads' => true,
            'platform.marketing' => true,
        ];

        $this->grantPermissions('roles', $newPermissions);
        $this->grantPermissions('users', $newPermissions);
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_comments');

        Schema::table('leads', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }

    /**
     * @param  array<string, bool>  $newPermissions
     */
    private function grantPermissions(string $table, array $newPermissions): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'permissions')) {
            return;
        }

        DB::table($table)->orderBy('id')->each(function (object $row) use ($table, $newPermissions): void {
            $permissions = json_decode((string) ($row->permissions ?? ''), true);
            if (! is_array($permissions)) {
                $permissions = [];
            }

            $merged = array_merge($permissions, $newPermissions);

            DB::table($table)->where('id', $row->id)->update([
                'permissions' => json_encode($merged),
            ]);
        });
    }
};
