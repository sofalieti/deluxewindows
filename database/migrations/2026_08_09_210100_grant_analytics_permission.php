<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $newPermissions = [
            'platform.analytics' => true,
        ];

        $this->grantPermissions('roles', $newPermissions);
        $this->grantPermissions('users', $newPermissions);
    }

    public function down(): void
    {
        // Intentionally keep granted permissions.
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
