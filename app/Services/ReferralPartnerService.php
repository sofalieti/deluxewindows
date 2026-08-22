<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ReferralApplication;
use App\Models\ReferralPartner;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Orchid\Platform\Models\Role;

class ReferralPartnerService
{
    public const ROLE_SLUG = 'referral-partner';

    public const PERMISSION_PORTAL = 'platform.referral.portal';

    public const PERMISSION_ADMIN = 'platform.referral.admin';

    /**
     * Approve a landing application: create Orchid user + active partner + code.
     *
     * @return array{partner: ReferralPartner, user: User, plain_password: string}
     */
    public function approveApplication(ReferralApplication $application, User $admin, ?string $code = null): array
    {
        return DB::transaction(function () use ($application, $admin, $code): array {
            $email = strtolower(trim($application->email));
            $plainPassword = Str::password(12);

            $user = User::query()->where('email', $email)->first();
            if ($user === null) {
                $user = User::query()->create([
                    'name' => $application->full_name,
                    'email' => $email,
                    'password' => Hash::make($plainPassword),
                ]);
            } else {
                $user->forceFill([
                    'password' => Hash::make($plainPassword),
                ])->save();
            }

            $permissions = is_array($user->permissions) ? $user->permissions : [];
            $permissions[self::PERMISSION_PORTAL] = true;
            $user->forceFill(['permissions' => $permissions])->save();

            $this->ensurePartnerRole();
            $role = Role::query()->where('slug', self::ROLE_SLUG)->first();
            if ($role !== null) {
                $user->replaceRoles([$role->id]);
            }

            $partnerCode = $code !== null && trim($code) !== ''
                ? \Illuminate\Support\Str::slug(trim($code))
                : ReferralPartner::generateUniqueCode($application->full_name);

            if ($partnerCode === '') {
                $partnerCode = ReferralPartner::generateUniqueCode('partner');
            }

            if (ReferralPartner::query()->where('code', $partnerCode)->exists()) {
                $partnerCode = ReferralPartner::generateUniqueCode($partnerCode);
            }

            $partner = ReferralPartner::query()->updateOrCreate(
                ['application_id' => $application->id],
                [
                    'user_id' => $user->id,
                    'code' => $partnerCode,
                    'name' => $application->full_name,
                    'email' => $email,
                    'phone' => $application->phone,
                    'status' => ReferralPartner::STATUS_ACTIVE,
                ]
            );

            // Ensure unique user_id link if partner existed without user.
            if ((int) $partner->user_id !== (int) $user->id) {
                $partner->forceFill(['user_id' => $user->id])->save();
            }

            $application->forceFill([
                'status' => ReferralApplication::STATUS_APPROVED,
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
            ])->save();

            return [
                'partner' => $partner->refresh(),
                'user' => $user->refresh(),
                'plain_password' => $plainPassword,
            ];
        });
    }

    public function rejectApplication(ReferralApplication $application, User $admin): void
    {
        $application->forceFill([
            'status' => ReferralApplication::STATUS_REJECTED,
            'reviewed_at' => now(),
            'reviewed_by' => $admin->id,
        ])->save();
    }

    public function ensurePartnerRole(): Role
    {
        $role = Role::query()->where('slug', self::ROLE_SLUG)->first();
        if ($role !== null) {
            $permissions = is_array($role->permissions) ? $role->permissions : [];
            if (empty($permissions[self::PERMISSION_PORTAL])) {
                $permissions[self::PERMISSION_PORTAL] = true;
                $role->forceFill(['permissions' => $permissions])->save();
            }

            return $role;
        }

        return Role::query()->create([
            'name' => 'Referral Partner',
            'slug' => self::ROLE_SLUG,
            'permissions' => [
                self::PERMISSION_PORTAL => true,
            ],
        ]);
    }
}
