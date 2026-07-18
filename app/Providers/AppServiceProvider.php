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

        View::composer(['layouts.classic', 'faq'], function (BladeView $view): void {
            $metadata = app(PageMetadataRepository::class)->current();

            $view->with([
                'pageMetadata' => $metadata,
                'pageSchemas' => app(SchemaBuilder::class)->build($metadata),
            ]);
        });
    }
}
