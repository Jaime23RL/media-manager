<?php

namespace App\Providers;

use App\Services\SettingService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureSettingsOverrides();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Override config values with database settings.
     */
    protected function configureSettingsOverrides(): void
    {
        if ($this->app->runningInConsole() && ! $this->app->bound('db')) {
            return;
        }

        try {
            $this->overrideMediaConfig();
        } catch (\Throwable) {
            // Database may not be available during initial setup.
        }
    }

    protected function overrideMediaConfig(): void
    {
        $animes = SettingService::get('media.paths.animes');
        if ($animes !== null) {
            config()->set('media.paths.animes', $animes);
        }

        $peliculas = SettingService::get('media.paths.peliculas');
        if ($peliculas !== null) {
            config()->set('media.paths.peliculas', $peliculas);
        }

        $extensions = SettingService::get('media.video_extensions');
        if ($extensions !== null) {
            config()->set('media.video_extensions', array_filter(explode(',', $extensions)));
        }

        $apiKey = SettingService::get('media.tmdb.api_key');
        if ($apiKey !== null) {
            config()->set('media.tmdb.api_key', $apiKey);
        }

        $language = SettingService::get('media.tmdb.language');
        if ($language !== null) {
            config()->set('media.tmdb.language', $language);
        }

        $qbUrl = SettingService::get('media.qbittorrent.url');
        if ($qbUrl !== null) {
            config()->set('media.qbittorrent.url', $qbUrl);
        }

        $qbUser = SettingService::get('media.qbittorrent.user');
        if ($qbUser !== null) {
            config()->set('media.qbittorrent.user', $qbUser);
        }

        $qbPass = SettingService::get('media.qbittorrent.password');
        if ($qbPass !== null) {
            config()->set('media.qbittorrent.password', $qbPass);
        }

        $nyaaBase = SettingService::get('media.nyaa.base_url');
        if ($nyaaBase !== null) {
            config()->set('media.nyaa.base_url', $nyaaBase);
        }

        $nyaaSubmitter = SettingService::get('media.nyaa.default_submitter');
        if ($nyaaSubmitter !== null) {
            config()->set('media.nyaa.default_submitter', $nyaaSubmitter);
        }

        $nyaaQuality = SettingService::get('media.nyaa.default_quality');
        if ($nyaaQuality !== null) {
            config()->set('media.nyaa.default_quality', $nyaaQuality);
        }

        $nyaaConcurrency = SettingService::get('media.nyaa.concurrency');
        if ($nyaaConcurrency !== null) {
            config()->set('media.nyaa.concurrency', (int) $nyaaConcurrency);
        }

        $nyaaCache = SettingService::get('media.nyaa.cache_ttl');
        if ($nyaaCache !== null) {
            config()->set('media.nyaa.cache_ttl', (int) $nyaaCache);
        }
    }
}
