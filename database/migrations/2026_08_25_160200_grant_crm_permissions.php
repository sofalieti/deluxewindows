<?php

declare(strict_types=1);

use App\Models\CrmTask;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $grants = [
            CrmTask::PERMISSION => true,
            CrmTask::PERMISSION_ALL => true,
        ];

        $this->grantPermissions('roles', $grants);
        $this->grantPermissions('users', $grants);
    }

    public function down(): void
    {
        $keys = [CrmTask::PERMISSION, CrmTask::PERMISSION_ALL];

        foreach (['roles', 'users'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'permissions')) {
                continue;
            }

            DB::table($table)->orderBy('id')->each(function (object $row) use ($table, $keys): void {
                $permissions = json_decode((string) ($row->permissions ?? ''), true);
                if (! is_array($permissions)) {
                    return;
                }

                foreach ($keys as $key) {
                    unset($permissions[$key]);
                }

                DB::table($table)->where('id', $row->id)->update([
                    'permissions' => json_encode($permissions),
                ]);
            });
        }
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

            DB::table($table)->where('id', $row->id)->update([
                'permissions' => json_encode(array_merge($permissions, $newPermissions)),
            ]);
        });
    }
};
