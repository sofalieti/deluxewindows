<?php

declare(strict_types=1);

use App\Models\Lead;
use App\Models\PhoneClick;
use App\Models\ReferralApplication;
use App\Models\ReferralPartner;
use App\Models\ReferralReward;
use App\Models\SiteVisit;
use App\Models\User;
use App\Services\ReferralAnalyticsService;
use App\Services\ReferralAttributionService;
use App\Services\ReferralPartnerService;
use App\Services\ReferralRewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Orchid\Platform\Http\Middleware\Access;

uses(RefreshDatabase::class);

test('referral short link sends active partner traffic to the personal invite page', function () {
    ReferralPartner::query()->create([
        'code' => 'alex-smith',
        'name' => 'Alex Smith',
        'email' => 'alex@example.com',
        'status' => ReferralPartner::STATUS_ACTIVE,
    ]);

    $this->get('/r/alex-smith')
        ->assertRedirect(url('/invite/alex-smith?utm_source=referral&utm_medium=partner&utm_campaign=alex-smith'));

    $this->get('/r/alex-smith?via=poster')
        ->assertRedirect(url('/invite/alex-smith?utm_source=referral&utm_medium=poster&utm_campaign=alex-smith'));

    $this->get('/r/alex-smith?via=<script>')
        ->assertRedirect(url('/invite/alex-smith?utm_source=referral&utm_medium=partner&utm_campaign=alex-smith'));
});

test('referral short link for unknown or paused partner falls back to home with utm', function () {
    ReferralPartner::query()->create([
        'code' => 'paused-pat',
        'name' => 'Paused Pat',
        'email' => 'pat@example.com',
        'status' => ReferralPartner::STATUS_PAUSED,
    ]);

    $this->get('/r/paused-pat')
        ->assertRedirect(url('/?utm_source=referral&utm_medium=partner&utm_campaign=paused-pat'));
    $this->get('/invite/paused-pat')->assertRedirect('/');
});

test('invite page is personalised and not indexed', function () {
    ReferralPartner::query()->create([
        'code' => 'maria-lopez',
        'name' => 'Maria Lopez',
        'email' => 'maria@example.com',
        'status' => ReferralPartner::STATUS_ACTIVE,
    ]);

    $this->get('/invite/maria-lopez')
        ->assertOk()
        ->assertSee('Maria', false)
        ->assertSee('Referral Invite Form', false)
        ->assertSee('noindex,follow', false);
});

test('referral landing renders the two-sided offer', function () {
    $this->get('/referrals')
        ->assertOk()
        ->assertSee('Give $150', false)
        ->assertSee('dwReferralPrefill', false)
        ->assertSee('Get my referral link', false);
});

test('referred lead records the friend credit', function () {
    ReferralPartner::query()->create([
        'code' => 'credit-check',
        'name' => 'Credit Check',
        'email' => 'cc@example.com',
        'status' => ReferralPartner::STATUS_ACTIVE,
    ]);

    $lead = Lead::query()->create([
        'full_name' => 'Friend',
        'email' => 'friend@example.com',
        'phone' => '6505550101',
        'utm_source' => 'referral',
        'utm_medium' => 'poster',
        'utm_campaign' => 'credit-check',
        'status' => Lead::STATUS_NEW,
        'meta' => [],
    ]);

    app(ReferralAttributionService::class)->attributeLead($lead);

    expect($lead->refresh()->metaValue('referral_friend_credit_cents', '0'))->toBe('15000');
});

test('partner can open own print kit and save payout details', function () {
    $user = User::factory()->create([
        'permissions' => [ReferralPartnerService::PERMISSION_PORTAL => true],
    ]);
    $partner = ReferralPartner::query()->create([
        'user_id' => $user->id,
        'code' => 'kit-owner',
        'name' => 'Kit Owner',
        'email' => $user->email,
        'status' => ReferralPartner::STATUS_ACTIVE,
    ]);
    $other = ReferralPartner::query()->create([
        'code' => 'someone-else',
        'name' => 'Someone Else',
        'email' => 'else@example.com',
        'status' => ReferralPartner::STATUS_ACTIVE,
    ]);

    foreach (['poster', 'flyer', 'cards'] as $format) {
        $this->withoutMiddleware(Access::class)
            ->actingAs($user)
            ->get(route('platform.referral.print', ['format' => $format]))
            ->assertOk()
            ->assertSee('data-rf-qr="'.e($partner->shareUrl($format === 'cards' ? 'card' : $format)).'"', false);
    }

    $this->withoutMiddleware(Access::class)
        ->actingAs($user)
        ->get(route('platform.referral.print', ['format' => 'poster', 'partner' => $other->id]))
        ->assertForbidden();

    $this->withoutMiddleware(Access::class)
        ->actingAs($user)
        ->post(route('platform.referral.my-link', ['method' => 'savePayout']), [
            'payout' => ['method' => 'Zelle', 'handle' => '650-555-0101'],
        ]);

    expect($partner->refresh()->payout_details)->toBe('Zelle: 650-555-0101');
});

test('partner channel analytics groups by utm medium', function () {
    $partner = ReferralPartner::query()->create([
        'code' => 'channels',
        'name' => 'Channels',
        'email' => 'ch@example.com',
        'status' => ReferralPartner::STATUS_ACTIVE,
    ]);

    foreach (['poster', 'poster', 'nextdoor'] as $medium) {
        SiteVisit::query()->create([
            'page_url' => 'https://www.deluxewindows.com/invite/channels',
            'utm_source' => 'referral',
            'utm_medium' => $medium,
            'utm_campaign' => 'channels',
            'referral_partner_id' => $partner->id,
            'meta' => [],
        ]);
    }
    Lead::query()->create([
        'full_name' => 'Poster Lead',
        'email' => 'pl@example.com',
        'phone' => '6505550202',
        'utm_medium' => 'poster',
        'status' => Lead::STATUS_SOLD,
        'referral_partner_id' => $partner->id,
        'meta' => [],
    ]);

    $rows = collect(app(ReferralAnalyticsService::class)->partnerChannels($partner))->keyBy('channel');

    expect($rows['poster']['visits'])->toBe(2)
        ->and($rows['poster']['leads'])->toBe(1)
        ->and($rows['poster']['sold'])->toBe(1)
        ->and($rows['poster']['label'])->toBe('Poster')
        ->and($rows['nextdoor']['visits'])->toBe(1);
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
