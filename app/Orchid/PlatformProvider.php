<?php

declare(strict_types=1);

namespace App\Orchid;

use App\Services\TrafficSourceVisibility;
use App\Support\WebflowCollectionRegistry;
use Orchid\Platform\Dashboard;
use Orchid\Platform\ItemPermission;
use Orchid\Platform\OrchidServiceProvider;
use Orchid\Screen\Actions\Menu;

class PlatformProvider extends OrchidServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(Dashboard $dashboard): void
    {
        parent::boot($dashboard);

        // ...
    }

    /**
     * Register the application menu.
     *
     * @return Menu[]
     */
    public function menu(): array
    {
        // Collections hidden from the admin sidebar.
        $hiddenCollections = ['product', 'window-styles-copy', 'reference-links', 'test', 'sku'];

        // Collections moved into a dedicated "Towns And County" section (in this order).
        $townsCollections = ['window-replacement', 'county-hub-pages'];

        // Everything related to Windows (in this order).
        $windowCollections = ['windows', 'window-type', 'window-styles', 'brands'];

        // Everything related to Doors (in this order). "Door Brands" is a custom screen added below.
        $doorCollections = ['doors', 'door-types'];

        $groupedCollections = array_merge($townsCollections, $windowCollections, $doorCollections);

        $webflowCollections = array_values(array_filter(
            WebflowCollectionRegistry::all(),
            fn (array $collection) => ! in_array($collection['slug'], $hiddenCollections, true)
        ));

        $collectionsBySlug = collect($webflowCollections)->keyBy('slug');

        $cmsCollections = array_values(array_filter(
            $webflowCollections,
            fn (array $collection) => ! in_array($collection['slug'], $groupedCollections, true)
        ));

        $webflowMenus = [];
        foreach ($cmsCollections as $index => $collection) {
            $webflowMenus[] = Menu::make($collection['title'])
                ->icon('bs.database')
                ->route('platform.webflow.collection', ['collection' => $collection['slug']])
                ->permission('platform.webflow.manage')
                ->title($index === 0 ? 'Webflow CMS' : null);
        }

        $windowsMenu = [];
        foreach ($windowCollections as $slug) {
            $collection = $collectionsBySlug->get($slug);
            if ($collection === null) {
                continue;
            }
            $windowsMenu[] = Menu::make($collection['title'])
                ->icon('bs.window')
                ->route('platform.webflow.collection', ['collection' => $collection['slug']])
                ->permission('platform.webflow.manage')
                ->title($windowsMenu === [] ? 'Windows' : null);
        }

        $doorsMenu = [];
        foreach ($doorCollections as $slug) {
            $collection = $collectionsBySlug->get($slug);
            if ($collection === null) {
                continue;
            }
            $doorsMenu[] = Menu::make($collection['title'])
                ->icon('bs.door-open')
                ->route('platform.webflow.collection', ['collection' => $collection['slug']])
                ->permission('platform.webflow.manage')
                ->title($doorsMenu === [] ? 'Doors' : null);
        }

        $doorsMenu[] = Menu::make('Door Brands')
            ->icon('bs.door-open')
            ->route('platform.door-brands')
            ->permission('platform.webflow.manage')
            ->title($doorsMenu === [] ? 'Doors' : null);

        $townsMenu = [];
        foreach ($townsCollections as $slug) {
            $collection = $collectionsBySlug->get($slug);
            if ($collection === null) {
                continue;
            }
            $townsMenu[] = Menu::make($collection['title'])
                ->icon('bs.geo-alt')
                ->route('platform.webflow.collection', ['collection' => $collection['slug']])
                ->permission('platform.webflow.manage')
                ->title($townsMenu === [] ? 'Towns And County' : null);
        }

        $marketingMenu = [
            Menu::make('Promotions')
                ->icon('bs.megaphone')
                ->route('platform.promotions')
                ->permission('platform.marketing')
                ->title('Marketing'),

            Menu::make('Leads')
                ->icon('bs.inbox')
                ->route('platform.leads')
                ->permission('platform.leads'),

            Menu::make('Contacts')
                ->icon('bs.person-lines-fill')
                ->route('platform.contacts')
                ->permission('platform.contacts'),

            Menu::make('Phone clicks')
                ->icon('bs.telephone')
                ->route('platform.phone-clicks')
                ->permission('platform.phone-clicks'),

            Menu::make('Visits')
                ->icon('bs.geo-alt')
                ->route('platform.visits')
                ->permission('platform.visits'),

            Menu::make('RingCentral calls')
                ->icon('bs.telephone')
                ->route('platform.ringcentral-calls')
                ->permission('platform.leads'),

            Menu::make('Call Analytics')
                ->icon('bs.graph-up')
                ->route('platform.analytics.calls')
                ->permission('platform.analytics'),

            Menu::make('Referral applications')
                ->icon('bs.person-plus')
                ->route('platform.referral.applications')
                ->permission('platform.referral.admin')
                ->title('Referrals'),

            Menu::make('Referral partners')
                ->icon('bs.people')
                ->route('platform.referral.partners')
                ->permission('platform.referral.admin'),

            Menu::make('Referral rewards')
                ->icon('bs.cash-coin')
                ->route('platform.referral.rewards')
                ->permission('platform.referral.admin'),

            Menu::make('Referral analytics')
                ->icon('bs.bar-chart')
                ->route('platform.referral.analytics')
                ->permission('platform.referral.admin'),

            Menu::make('My dashboard')
                ->icon('bs.speedometer2')
                ->route('platform.referral.my-dashboard')
                ->permission('platform.referral.portal')
                ->title('Partner portal'),

            Menu::make('My leads')
                ->icon('bs.inbox')
                ->route('platform.referral.my-leads')
                ->permission('platform.referral.portal'),

            Menu::make('My traffic')
                ->icon('bs.activity')
                ->route('platform.referral.my-traffic')
                ->permission('platform.referral.portal'),

            Menu::make('My rewards')
                ->icon('bs.cash')
                ->route('platform.referral.my-rewards')
                ->permission('platform.referral.portal'),

            Menu::make('My link')
                ->icon('bs.link-45deg')
                ->route('platform.referral.my-link')
                ->permission('platform.referral.portal'),

            Menu::make('Mailbox')
                ->icon('bs.envelope')
                ->route('platform.mailbox')
                ->permission('platform.mailbox'),

            Menu::make('Content datasets')
                ->icon('bs.database')
                ->route('platform.content-datasets')
                ->permission('platform.marketing'),

            Menu::make('Sitemap.xml')
                ->icon('bs.file-earmark-code')
                ->route('platform.sitemap')
                ->permission('platform.marketing'),
        ];

        $systemMenu = [
            Menu::make('Queue')
                ->icon('bs.list-task')
                ->route('platform.queue')
                ->permission('platform.queue')
                ->title('System'),
        ];

        $accessMenu = [
            Menu::make(__('Users'))
                ->icon('bs.people')
                ->route('platform.systems.users')
                ->permission('platform.systems.users')
                ->title(__('Access Controls')),

            Menu::make(__('Roles'))
                ->icon('bs.shield')
                ->route('platform.systems.roles')
                ->permission('platform.systems.roles'),
        ];

        return array_merge($marketingMenu, $windowsMenu, $doorsMenu, $webflowMenus, $townsMenu, $systemMenu, $accessMenu);
    }

    /**
     * Register permissions for the application.
     *
     * @return ItemPermission[]
     */
    public function permissions(): array
    {
        return [
            ItemPermission::group(__('System'))
                ->addPermission('platform.systems.roles', __('Roles'))
                ->addPermission('platform.systems.users', __('Users'))
                ->addPermission('platform.queue', 'Queue monitor'),

            ItemPermission::group('Leads')
                ->addPermission('platform.leads', 'Open Leads section'),

            $this->sourcePermissions(
                ItemPermission::group('Lead sources'),
                TrafficSourceVisibility::SECTION_LEADS
            ),

            ItemPermission::group('Phone clicks')
                ->addPermission('platform.phone-clicks', 'Open Phone clicks section'),

            $this->sourcePermissions(
                ItemPermission::group('Phone click sources'),
                TrafficSourceVisibility::SECTION_PHONE_CLICKS
            ),

            ItemPermission::group('Visits')
                ->addPermission('platform.visits', 'Open Visits section'),

            ItemPermission::group('Analytics')
                ->addPermission('platform.analytics', 'Open Call Analytics'),

            ItemPermission::group('Referrals')
                ->addPermission('platform.referral.admin', 'Manage referral partners, applications & rewards')
                ->addPermission('platform.referral.portal', 'Partner portal (own referral data only)'),

            ItemPermission::group('Contacts')
                ->addPermission('platform.contacts', 'Manage Contacts'),

            ItemPermission::group('Mailbox')
                ->addPermission('platform.mailbox', 'View and sync mailbox'),

            ItemPermission::group('Marketing')
                ->addPermission('platform.marketing', 'Promotions, datasets & sitemap'),

            ItemPermission::group('Webflow')
                ->addPermission('platform.webflow.manage', 'Manage Webflow Collections'),
        ];
    }

    private function sourcePermissions(ItemPermission $group, string $section): ItemPermission
    {
        foreach (TrafficSourceVisibility::bucketLabels() as $bucket => $label) {
            $group->addPermission(
                TrafficSourceVisibility::permission($section, $bucket),
                'View '.$label
            );
        }

        return $group;
    }
}
