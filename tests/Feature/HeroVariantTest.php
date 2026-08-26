<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\HeroVariantService;
use Orchid\Platform\Http\Middleware\Access;

use function Pest\Laravel\get;

/** Restores whatever the site-wide switch held before a test changed it. */
function withRestoredHeroVariant(callable $test): void
{
    $service = app(HeroVariantService::class);
    $before = $service->variant();

    try {
        $test($service);
    } finally {
        $service->update($before);
    }
}

test('home serves the new mobile hero by default', function () {
    $html = get('/')->assertOk()->getContent();

    expect($html)->toContain('data-hero-variant="new"')
        ->and($html)->toContain('data-hero-new')
        ->and($html)->toContain('div-block-59--hero-new')
        ->and($html)->toContain('mobile-sticky-cta')
        ->and($html)->toContain('Book a consultation')
        ->and($html)->not->toContain('mobile-fab-estimate');
});

test('hero query parameter rolls the old hero back and is remembered', function () {
    $response = get('/?hero=old')->assertOk();
    $html = $response->getContent();

    expect($html)->toContain('data-hero-variant="old"')
        ->and($html)->toContain('mobile-fab-estimate')
        ->and($html)->not->toContain('data-hero-new')
        ->and($html)->not->toContain('mobile-sticky-cta');

    $response->assertCookie(config('hero.cookie'), 'old');
});

test('city landing pages follow the active hero variant', function () {
    $html = get('/window-replacement/san-francisco')->assertOk()->getContent();

    expect($html)->toContain('data-hero-new')
        ->and($html)->toContain('div-block-59--hero-new');

    $old = get('/window-replacement/san-francisco?hero=old')->assertOk()->getContent();

    expect($old)->not->toContain('data-hero-new');
});

test('the mobile hero headline follows the page it sits on', function () {
    $home = get('/')->assertOk()->getContent();

    expect($home)->toContain('The same crew has been doing this for');

    $city = get('/window-replacement/san-francisco')->assertOk()->getContent();

    expect($city)->toContain('Windows &amp; Doors in')
        ->and($city)->toContain('San Francisco, San Francisco County.')
        ->and($city)->not->toContain('The same crew has been doing this for');

    $landing = get('/new-construction')->assertOk()->getContent();

    expect($landing)->toContain('Building a New Home? Get Every Window &amp; Door from One Team')
        ->and($landing)->not->toContain('The same crew has been doing this for');
});

test('dashboard renders the hero switch', function () {
    $user = User::factory()->create([
        'permissions' => ['platform.index' => true],
    ]);

    $html = $this->withoutMiddleware(Access::class)
        ->actingAs($user)
        ->get(route('platform.main'))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('hero[is_new]')
        ->and($html)->toContain('Use the new hero block')
        ->and($html)->toContain('Save hero block')
        ->and($html)->toContain('?hero=default');
});

test('dashboard switch changes the hero for the whole site', function () {
    withRestoredHeroVariant(function (HeroVariantService $service) {
        $service->update('new');

        $user = User::factory()->create([
            'permissions' => ['platform.index' => true],
        ]);

        $this->withoutMiddleware(Access::class)
            ->actingAs($user)
            ->post(route('platform.main', ['method' => 'saveHeroVariant']), [
                'hero' => ['is_new' => '0'],
            ])
            ->assertRedirect();

        expect($service->variant())->toBe('old');

        $html = get('/')->assertOk()->getContent();
        expect($html)->toContain('data-hero-variant="old"');

        $city = get('/window-replacement/san-francisco')->assertOk()->getContent();
        expect($city)->not->toContain('data-hero-new');
    });
});

test('hero=default drops a personal override and follows the dashboard switch', function () {
    withRestoredHeroVariant(function (HeroVariantService $service) {
        $service->update('new');

        $response = $this->withCookie(config('hero.cookie'), 'old')->get('/?hero=default');

        $response->assertOk()->assertCookieExpired(config('hero.cookie'));
        expect($response->getContent())->toContain('data-hero-variant="new"');
    });
});

test('texting options stay out of the phone choice modal while disabled', function () {
    expect(config('hero.phone_modal_sms'))->toBeFalse();

    $html = get('/')->assertOk()->getContent();

    expect($html)->toContain('data-bing-phone-action="call"')
        ->and($html)->toContain('data-bing-phone-action="callback"')
        ->and($html)->not->toContain('data-bing-phone-action="text"')
        ->and($html)->not->toContain('data-bing-phone-action="whatsapp"');
});
