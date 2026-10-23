<?php

declare(strict_types=1);

use App\Models\Lead;
use App\Models\PhoneClick;
use App\Models\SiteVisit;
use App\Models\User;
use App\Models\VisitsSetting;
use App\Services\CallAnalyticsService;
use App\Services\ScreenSizeStatsService;
use App\Services\VisitsSettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Orchid\Platform\Http\Middleware\Access;

uses(RefreshDatabase::class);

test('visit, lead, and phone click store the first screen size', function () {
    VisitsSetting::query()->updateOrCreate(['scope' => 'default'], ['enabled' => true]);
    app(VisitsSettingsService::class)->forgetCache();
    Queue::fake();

    $this->postJson('/visit', [
        'page_url' => 'https://www.deluxewindows.com/',
        'screen_width' => 1366,
        'screen_height' => 768,
    ])->assertOk();

    $this->postJson('/visit', [
        'page_url' => 'https://www.deluxewindows.com/',
        'screen_width' => 12,
        'screen_height' => 99999,
    ])->assertOk();

    $this->postJson('/contact-form', [
        'Name' => 'Pat Example',
        'Phone' => '6505550101',
        'Email' => 'pat-screen@example.com',
        'window_count' => '6-10',
        'opening_kind' => 'windows',
        'page_url' => 'https://www.deluxewindows.com/',
        'screen_width' => 1366,
        'screen_height' => 768,
    ])->assertOk();

    $this->postJson('/phone-click', [
        'phone' => '+16505551212',
        'page_url' => 'https://www.deluxewindows.com/',
        'screen_width' => 1920,
        'screen_height' => 1080,
    ])->assertOk();

    expect(SiteVisit::query()->orderBy('id')->pluck('screen_width')->all())->toBe([1366, null])
        ->and(Lead::query()->where('email', 'pat-screen@example.com')->firstOrFail()->screen_width)->toBe(1366)
        ->and(PhoneClick::query()->latest('id')->firstOrFail()->screen_height)->toBe(1080);
});

test('screen size stats group width by 50px and ignore spam and missed calls', function () {
    $now = CarbonImmutable::now();

    foreach ([1366, 1390, 1920, 1920] as $width) {
        SiteVisit::query()->create([
            'page_url' => 'https://www.deluxewindows.com/',
            'screen_width' => $width,
            'screen_height' => 800,
            'created_at' => $now,
        ]);
    }

    SiteVisit::query()->create([
        'page_url' => 'https://www.deluxewindows.com/',
        'created_at' => $now,
    ]);

    Lead::query()->create([
        'full_name' => 'Real Lead',
        'email' => 'real-screen@example.com',
        'phone' => '6505550101',
        'screen_width' => 1366,
        'screen_height' => 768,
        'created_at' => $now,
    ]);
    Lead::query()->create([
        'full_name' => 'Spam Lead',
        'email' => 'spam-screen@example.com',
        'phone' => '6505550102',
        'status' => Lead::STATUS_SPAM,
        'screen_width' => 1366,
        'screen_height' => 768,
        'created_at' => $now,
    ]);

    PhoneClick::query()->create([
        'phone' => '+16505551111',
        'screen_width' => 1366,
        'screen_height' => 768,
        'ringcentral_status' => PhoneClick::RINGCENTRAL_FOUND,
        'ringcentral_result' => 'Accepted',
        'ringcentral_duration' => 40,
        'created_at' => $now,
    ]);
    PhoneClick::query()->create([
        'phone' => '+16505552222',
        'screen_width' => 1366,
        'screen_height' => 768,
        'ringcentral_status' => PhoneClick::RINGCENTRAL_FOUND,
        'ringcentral_result' => 'Missed',
        'ringcentral_duration' => 0,
        'created_at' => $now,
    ]);

    $rows = collect(app(ScreenSizeStatsService::class)->rows($now->subDay(), $now->addDay()))
        ->keyBy('bucket');

    expect($rows[1350]['visits'])->toBe(2)
        ->and($rows[1350]['leads'])->toBe(1)
        ->and($rows[1350]['lead_rate'])->toBe(50.0)
        ->and($rows[1350]['calls'])->toBe(1)
        ->and($rows[1350]['call_rate'])->toBe(50.0)
        ->and($rows[1900]['visits'])->toBe(2)
        ->and($rows[1900]['leads'])->toBe(0)
        ->and($rows[1900]['call_rate'])->toBe(0.0)
        ->and($rows->has(0))->toBeFalse();
});

test('screen size screen filters a custom period', function () {
    $user = User::factory()->create([
        'permissions' => [
            'platform.index' => true,
            'platform.analytics' => true,
        ],
    ]);

    SiteVisit::query()->create([
        'page_url' => 'https://www.deluxewindows.com/',
        'screen_width' => 1366,
        'screen_height' => 768,
    ])->forceFill([
        'created_at' => CarbonImmutable::parse('2026-03-02 12:00:00', 'America/Los_Angeles'),
    ])->save();
    SiteVisit::query()->create([
        'page_url' => 'https://www.deluxewindows.com/',
        'screen_width' => 800,
        'screen_height' => 600,
    ])->forceFill([
        'created_at' => CarbonImmutable::parse('2026-01-01 12:00:00', 'America/Los_Angeles'),
    ])->save();

    $content = $this->withoutMiddleware(Access::class)
        ->actingAs($user)
        ->get(route('platform.analytics.screen-sizes', [
            'filter' => [
                'preset' => CallAnalyticsService::PRESET_CUSTOM,
                'range' => ['start' => '2026-03-01', 'end' => '2026-03-03'],
            ],
        ]))
        ->assertOk()
        ->getContent();

    preg_match('/Period:([^<]{0,120})/', $content, $period);

    expect($period[1] ?? 'missing period')->toContain('Mar 1, 2026')
        ->and($content)->toContain('1350-1399')
        ->and($content)->not->toContain('800-849');
});
