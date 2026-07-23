<?php

namespace App\Providers;

use App\Services\Seo\PageMetadataRepository;
use App\Services\Seo\SchemaBuilder;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View as BladeView;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\Media\ImageThumbnailService::class);
        $this->app->singleton(\App\Services\PromotionSettingsService::class);
        $this->app->singleton(PageMetadataRepository::class);
        $this->app->singleton(SchemaBuilder::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Must run for every view: @section content renders before layouts.classic,
        // so a layout-only composer never reaches seo-h1 / FAQ-in-content includes.
        View::composer('*', function (BladeView $view): void {
            if ($view->offsetExists('pageMetadata')) {
                return;
            }

            $name = (string) ($view->name() ?? '');
            if ($name !== '' && $this->shouldSkipPageMetadataComposer($name)) {
                return;
            }

            static $metadata = null;
            static $schemas = null;

            if ($metadata === null) {
                $metadata = app(PageMetadataRepository::class)->current();
                $schemas = app(SchemaBuilder::class)->build($metadata);
            }

            $view->with([
                'pageMetadata' => $metadata,
                'pageSchemas' => $schemas,
            ]);
        });
    }

    private function shouldSkipPageMetadataComposer(string $viewName): bool
    {
        foreach ([
            'orchid::',
            'platform::',
            'mail.',
            'notifications.',
            'vendor.',
            'errors::',
            'pagination::',
            'components.',
        ] as $prefix) {
            if (str_starts_with($viewName, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
