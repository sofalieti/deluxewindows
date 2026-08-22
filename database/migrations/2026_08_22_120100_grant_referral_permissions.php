<?php

declare(strict_types=1);

use App\Services\ReferralPartnerService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchid\Platform\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $grants = [
            ReferralPartnerService::PERMISSION_ADMIN => true,
        ];

        $this->grantPermissions('roles', $grants);
        $this->grantPermissions('users', $grants);

        if (! Schema::hasTable('roles')) {
            return;
        }

        $existing = Role::query()->where('slug', ReferralPartnerService::ROLE_SLUG)->first();
        if ($existing === null) {
            Role::query()->create([
                'name' => 'Referral Partner',
                'slug' => ReferralPartnerService::ROLE_SLUG,
                'permissions' => [
                    ReferralPartnerService::PERMISSION_PORTAL => true,
                ],
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('roles')) {
            Role::query()->where('slug', ReferralPartnerService::ROLE_SLUG)->delete();
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
