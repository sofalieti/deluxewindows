<?php

declare(strict_types=1);

use App\Models\Lead;
use App\Models\PhoneClick;
use App\Services\TrafficSourceVisibility;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leads') && ! Schema::hasColumn('leads', 'traffic_source')) {
            Schema::table('leads', function (Blueprint $table): void {
                $table->string('traffic_source', 32)->nullable()->after('utm_campaign')->index();
            });
        }

        if (Schema::hasTable('phone_clicks') && ! Schema::hasColumn('phone_clicks', 'traffic_source')) {
            Schema::table('phone_clicks', function (Blueprint $table): void {
                $table->string('traffic_source', 32)->nullable()->after('utm_redirect')->index();
            });
        }

        $this->backfill('leads', Lead::class);
        $this->backfill('phone_clicks', PhoneClick::class);

        $grants = TrafficSourceVisibility::allGrantPayload();
        $this->grantPermissions('roles', $grants);
        $this->grantPermissions('users', $grants);
    }

    public function down(): void
    {
        if (Schema::hasTable('leads') && Schema::hasColumn('leads', 'traffic_source')) {
            Schema::table('leads', function (Blueprint $table): void {
                $table->dropColumn('traffic_source');
            });
        }

        if (Schema::hasTable('phone_clicks') && Schema::hasColumn('phone_clicks', 'traffic_source')) {
            Schema::table('phone_clicks', function (Blueprint $table): void {
                $table->dropColumn('traffic_source');
            });
        }
    }

    /**
     * @param  class-string<Lead|PhoneClick>  $modelClass
     */
    private function backfill(string $table, string $modelClass): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'traffic_source')) {
            return;
        }

        $modelClass::query()
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($table): void {
                foreach ($rows as $row) {
                    DB::table($table)->where('id', $row->id)->update([
                        'traffic_source' => $row->trafficSourceKey(),
                    ]);
                }
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

            DB::table($table)->where('id', $row->id)->update([
                'permissions' => json_encode(array_merge($permissions, $newPermissions)),
            ]);
        });
    }
};
