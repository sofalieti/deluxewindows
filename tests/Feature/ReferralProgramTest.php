<?php

declare(strict_types=1);

use App\Models\Lead;
use App\Models\PhoneClick;
use App\Models\ReferralApplication;
use App\Models\ReferralPartner;
use App\Models\ReferralReward;
use App\Models\SiteVisit;
use App\Models\User;
use App\Services\ReferralAttributionService;
use App\Services\ReferralPartnerService;
use App\Services\ReferralRewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('referral short link redirects with partner utm params', function () {
    ReferralPartner::query()->create([
        'code' => 'alex-smith',
        'name' => 'Alex Smith',
        'email' => 'alex@example.com',
        'status' => ReferralPartner::STATUS_ACTIVE,
    ]);

    $this->get('/r/alex-smith')
        ->assertRedirect('/?utm_source=referral&utm_medium=partner&utm_campaign=alex-smith');
});

test('attribution stamps referral_partner_id on lead phone click and visit', function () {
    $partner = ReferralPartner::query()->create([
        'code' => 'partner-one',
        'name' => 'Partner One',
        'email' => 'p1@example.com',
        'status' => ReferralPartner::STATUS_ACTIVE,
    ]);

    $lead = Lead::query()->create([
        'full_name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '6505551212',
        'utm_source' => 'referral',
        'utm_medium' => 'partner',
        'utm_campaign' => 'partner-one',
        'status' => Lead::STATUS_NEW,
        'meta' => [],
    ]);

    app(ReferralAttributionService::class)->attributeLead($lead);
    expect($lead->refresh()->referral_partner_id)->toBe($partner->id);

    $click = PhoneClick::query()->create([
        'phone' => '+16504614446',
        'utm_source' => 'referral',
        'utm_campaign' => 'partner-one',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_PENDING,
        'meta' => [],
    ]);
    app(ReferralAttributionService::class)->attributePhoneClick($click);
    expect($click->refresh()->referral_partner_id)->toBe($partner->id);

    $visit = SiteVisit::query()->create([
        'page_url' => 'https://www.deluxewindows.com/',
        'utm_source' => 'referral',
        'utm_campaign' => 'partner-one',
        'meta' => [],
    ]);
    app(ReferralAttributionService::class)->attributeVisit($visit);
    expect($visit->refresh()->referral_partner_id)->toBe($partner->id);
});

test('non referral traffic is not attributed even with random campaign', function () {
    ReferralPartner::query()->create([
        'code' => 'partner-one',
        'name' => 'Partner One',
        'email' => 'p1@example.com',
        'status' => ReferralPartner::STATUS_ACTIVE,
    ]);

    // Campaign matches but partner paused — should not attribute.
    $paused = ReferralPartner::query()->create([
        'code' => 'paused-code',
        'name' => 'Paused',
        'email' => 'paused@example.com',
        'status' => ReferralPartner::STATUS_PAUSED,
    ]);

    $lead = Lead::query()->create([
        'full_name' => 'No Match',
        'email' => 'nomatch@example.com',
        'phone' => '6505559999',
        'utm_source' => 'referral',
        'utm_campaign' => 'paused-code',
        'status' => Lead::STATUS_NEW,
        'meta' => [],
    ]);

    app(ReferralAttributionService::class)->attributeLead($lead);
    expect($lead->refresh()->referral_partner_id)->toBeNull();
    expect($paused->id)->toBeGreaterThan(0);
});

test('sold lead creates eligible reward and admin can approve then mark paid', function () {
    $admin = User::factory()->create();
    $partner = ReferralPartner::query()->create([
        'code' => 'earn-150',
        'name' => 'Earner',
        'email' => 'earn@example.com',
        'status' => ReferralPartner::STATUS_ACTIVE,
    ]);

    $lead = Lead::query()->create([
        'full_name' => 'Buyer',
        'email' => 'buyer@example.com',
        'phone' => '6505550001',
        'status' => Lead::STATUS_SOLD,
        'referral_partner_id' => $partner->id,
        'meta' => [],
    ]);

    $rewards = app(ReferralRewardService::class);
    $reward = $rewards->syncEligibleForLead($lead);

    expect($reward)->not->toBeNull()
        ->and($reward->status)->toBe(ReferralReward::STATUS_ELIGIBLE)
        ->and($reward->amount_cents)->toBe(15000);

    $rewards->approve($reward, $admin);
    expect($reward->refresh()->status)->toBe(ReferralReward::STATUS_APPROVED);

    $rewards->markPaid($reward, $admin);
    expect($reward->refresh()->status)->toBe(ReferralReward::STATUS_PAID);
});

test('approving an application creates user partner and portal permission', function () {
    $admin = User::factory()->create();
    $application = ReferralApplication::query()->create([
        'full_name' => 'Pat Partner',
        'email' => 'pat.partner@example.com',
        'phone' => '6505557777',
        'status' => ReferralApplication::STATUS_PENDING,
    ]);

    $result = app(ReferralPartnerService::class)->approveApplication($application, $admin);

    expect($application->refresh()->status)->toBe(ReferralApplication::STATUS_APPROVED)
        ->and($result['partner']->status)->toBe(ReferralPartner::STATUS_ACTIVE)
        ->and($result['partner']->user_id)->toBe($result['user']->id)
        ->and($result['user']->hasAccess(ReferralPartnerService::PERMISSION_PORTAL))->toBeTrue()
        ->and($result['plain_password'])->not->toBe('');
});

test('partner cannot see another partners leads in portal query scope', function () {
    $userA = User::factory()->create(['password' => Hash::make('secret')]);
    $userB = User::factory()->create(['password' => Hash::make('secret')]);

    $partnerA = ReferralPartner::query()->create([
        'user_id' => $userA->id,
        'code' => 'alpha',
        'name' => 'Alpha',
        'email' => $userA->email,
        'status' => ReferralPartner::STATUS_ACTIVE,
    ]);
    $partnerB = ReferralPartner::query()->create([
        'user_id' => $userB->id,
        'code' => 'beta',
        'name' => 'Beta',
        'email' => $userB->email,
        'status' => ReferralPartner::STATUS_ACTIVE,
    ]);

    Lead::query()->create([
        'full_name' => 'For Alpha',
        'email' => 'a@example.com',
        'phone' => '111',
        'status' => Lead::STATUS_NEW,
        'referral_partner_id' => $partnerA->id,
        'meta' => [],
    ]);
    Lead::query()->create([
        'full_name' => 'For Beta',
        'email' => 'b@example.com',
        'phone' => '222',
        'status' => Lead::STATUS_NEW,
        'referral_partner_id' => $partnerB->id,
        'meta' => [],
    ]);

    $visibleToA = Lead::query()->where('referral_partner_id', $partnerA->id)->pluck('full_name')->all();
    expect($visibleToA)->toBe(['For Alpha'])
        ->and(Lead::query()->where('referral_partner_id', $partnerB->id)->count())->toBe(1);
});

test('referral landing accepts an application', function () {
    $this->post('/referrals/apply', [
        'full_name' => 'New Applicant',
        'email' => 'applicant@example.com',
        'phone' => '6505554321',
        'message' => 'I have a big audience',
    ])->assertRedirect();

    expect(ReferralApplication::query()->where('email', 'applicant@example.com')->exists())->toBeTrue();
});
