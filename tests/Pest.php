<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Full Orchid permissions for lead / phone-click screens including every traffic source.
 *
 * @return array<string, bool>
 */
function adminTrafficPermissions(bool $leads = true, bool $phoneClicks = true): array
{
    $permissions = [];

    if ($leads) {
        $permissions['platform.leads'] = true;
        $permissions = array_merge(
            $permissions,
            \App\Services\TrafficSourceVisibility::sourceGrantPayload(
                \App\Services\TrafficSourceVisibility::SECTION_LEADS
            )
        );
    }

    if ($phoneClicks) {
        $permissions['platform.phone-clicks'] = true;
        $permissions = array_merge(
            $permissions,
            \App\Services\TrafficSourceVisibility::sourceGrantPayload(
                \App\Services\TrafficSourceVisibility::SECTION_PHONE_CLICKS
            )
        );
    }

    return $permissions;
}
