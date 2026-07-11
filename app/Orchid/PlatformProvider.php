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

        $webflowCollections = array_values(array_filter(
            WebflowCollectionRegistry::all(),
            fn (array $collection) => ! in_array($collection['slug'], $hiddenCollections, true)
        ));

        $webflowMenus = [];
        foreach ($webflowCollections as $index => $collection) {
            $webflowMenus[] = Menu::make($collection['title'])
                ->icon('bs.database')
                ->route('platform.webflow.collection', ['collection' => $collection['slug']])
                ->title($index === 0 ? 'Webflow CMS' : null);
        }

        $webflowMenus[] = Menu::make('Door Brands')
            ->icon('bs.door-open')
            ->route('platform.door-brands');

        $baseMenu = [
            Menu::make('Promotions')
                ->icon('bs.megaphone')
                ->route('platform.promotions')
                ->title('Marketing'),

            Menu::make('Leads')
                ->icon('bs.inbox')
                ->route('platform.leads'),

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

        return array_merge($webflowMenus, $baseMenu);
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
