<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        $schedule->command('promotions:apply-calendar')
            ->everyThreeHours()
            ->timezone('America/Los_Angeles')
            ->withoutOverlapping();

        $schedule->command('mailbox:sync')
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->when(fn () => \Illuminate\Support\Facades\Schema::hasTable('mailbox_settings')
                && \App\Models\MailboxSetting::query()->where('scope', 'default')->where('enabled', true)->exists());

        $schedule->command('ringcentral:sync-calls')
            ->hourly()
            ->timezone('America/Los_Angeles')
            ->withoutOverlapping()
            ->when(fn () => filled(config('services.ringcentral.client_id'))
                && filled(config('services.ringcentral.client_secret'))
                && filled(config('services.ringcentral.jwt')));

        $schedule->command('ads:backfill-offline-conversions --days=7')
            ->hourly()
            ->withoutOverlapping()
            ->when(fn () => \Illuminate\Support\Facades\Schema::hasColumn('phone_clicks', 'bing_ads_conversion_sent_at')
                && (filled(config('services.microsoft_ads.refresh_token'))
                    || filled(config('services.google_ads.refresh_token'))));

        $schedule->command('ads:export-google-offline-sheet')
            ->dailyAt('00:05')
            ->timezone('America/Los_Angeles')
            ->withoutOverlapping()
            ->when(fn () => \Illuminate\Support\Facades\Schema::hasColumn('phone_clicks', 'google_ads_sheet_exported_at')
                && filled(config('services.google_drive.folder_id'))
                && (
                    (config('services.google_drive.auth') === 'oauth' && filled(config('services.google_drive.refresh_token')))
                    || filled(config('services.google_drive.service_account_json'))
                ));

        $schedule->command('ringcentral:process-transcripts')
            ->everyTwoMinutes()
            ->withoutOverlapping()
            ->when(fn () => filled(config('services.openai.api_key'))
                && \Illuminate\Support\Facades\Schema::hasColumn('ringcentral_calls', 'transcript_status'));
    })
    ->create();
