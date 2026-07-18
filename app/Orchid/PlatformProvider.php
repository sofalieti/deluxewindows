<?php

declare(strict_types=1);

namespace App\Orchid;

use App\Support\WebflowCollectionRegistry;
use Orchid\Platform\Dashboard;
use Orchid\Platform\ItemPermission;
use Orchid\Platform\OrchidServiceProvider;
use Orchid\Screen\Actions\Menu;

class PlatformProvider extends OrchidServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @param Dashboard $dashboard
     *
     * @return void
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
                ->title($doorsMenu === [] ? 'Doors' : null);
        }

        $doorsMenu[] = Menu::make('Door Brands')
            ->icon('bs.door-open')
            ->route('platform.door-brands')
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
                ->title($townsMenu === [] ? 'Towns And County' : null);
        }

        $marketingMenu = [
            Menu::make('Promotions')
                ->icon('bs.megaphone')
                ->route('platform.promotions')
                ->title('Marketing'),

            Menu::make('Leads')
                ->icon('bs.inbox')
                ->route('platform.leads'),

            Menu::make('Sitemap.xml')
                ->icon('bs.file-earmark-code')
                ->route('platform.sitemap'),
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

        return array_merge($marketingMenu, $windowsMenu, $doorsMenu, $webflowMenus, $townsMenu, $accessMenu);
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
                ->addPermission('platform.systems.users', __('Users')),

            ItemPermission::group('Webflow')
                ->addPermission('platform.webflow.manage', 'Manage Webflow Collections'),
        ];
    }
}
