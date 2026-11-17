<?php

use App\Models\Webflow\BrandCollectionsWebflowItem;
use App\Models\Webflow\BrandsWebflowItem;
use App\Models\Webflow\DoorsWebflowItem;
use App\Models\Webflow\WindowsWebflowItem;
use App\Orchid\Screens\Promotions\PromotionsScreen;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('promotion price lists follow the catalog order', function () {
    WindowsWebflowItem::query()->create([
        'webflow_item_id' => 'win-later',
        'is_archived' => false,
        'is_draft' => false,
        'field_data' => ['slug' => 'steel-windows', 'name' => 'Steel', 'parent-collection' => 'Windows', 'order' => 2],
    ]);
    WindowsWebflowItem::query()->create([
        'webflow_item_id' => 'win-first',
        'is_archived' => false,
        'is_draft' => false,
        'field_data' => ['slug' => 'vinyl-windows', 'name' => 'Vinyl', 'parent-collection' => 'Windows', 'order' => 1],
    ]);

    BrandCollectionsWebflowItem::query()->create([
        'webflow_item_id' => 'series-later',
        'is_archived' => false,
        'is_draft' => false,
        'field_data' => ['slug' => 'series-b', 'name' => 'Series B', 'order' => 2],
    ]);
    BrandCollectionsWebflowItem::query()->create([
        'webflow_item_id' => 'series-first',
        'is_archived' => false,
        'is_draft' => false,
        'field_data' => ['slug' => 'series-a', 'name' => 'Series A', 'order' => 1],
    ]);

    BrandsWebflowItem::query()->create([
        'webflow_item_id' => 'brand-later',
        'is_archived' => false,
        'is_draft' => false,
        'field_data' => ['slug' => 'milgard', 'name' => 'Milgard', 'order' => 2],
    ]);
    BrandsWebflowItem::query()->create([
        'webflow_item_id' => 'brand-first',
        'is_archived' => false,
        'is_draft' => false,
        'field_data' => ['slug' => 'marvin', 'name' => 'Marvin', 'order' => 1],
    ]);

    DoorsWebflowItem::query()->create([
        'webflow_item_id' => 'door-later',
        'is_archived' => false,
        'is_draft' => false,
        'field_data' => ['slug' => 'steel-doors', 'name' => 'Steel Doors', 'order' => 2],
    ]);
    DoorsWebflowItem::query()->create([
        'webflow_item_id' => 'door-first',
        'is_archived' => false,
        'is_draft' => false,
        'field_data' => ['slug' => 'vinyl-doors', 'name' => 'Vinyl Doors', 'order' => 1],
    ]);

    $screen = app(PromotionsScreen::class);
    $slugs = function (string $method) use ($screen): array {
        $reflection = new ReflectionMethod($screen, $method);

        return array_column($reflection->invoke($screen), 'slug');
    };

    expect($slugs('windowTypeItems'))->toBe(['vinyl-windows', 'steel-windows'])
        ->and($slugs('seriesItems'))->toBe(['series-a', 'series-b'])
        ->and($slugs('brandItems'))->toBe(['marvin', 'milgard'])
        ->and($slugs('doorItems'))->toBe(['vinyl-doors', 'steel-doors']);
});
