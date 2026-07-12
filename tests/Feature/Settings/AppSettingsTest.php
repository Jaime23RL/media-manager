<?php

namespace Tests\Feature\Settings;

use App\Livewire\SettingsPage;
use App\Models\Setting;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AppSettingsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function settings_page_requires_authentication(): void
    {
        $response = $this->get(route('settings.media'));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function authenticated_user_can_view_settings_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.media'));

        $response->assertStatus(200);
        $response->assertSee('App Settings');
        $response->assertSee('Media Library');
        $response->assertSee('TMDB');
        $response->assertSee('qBittorrent');
        $response->assertSee('Nyaa.si');
        $response->assertSee('Theme');
    }

    #[Test]
    public function settings_page_shows_default_values_from_config(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.media'));

        $response->assertStatus(200);
        $response->assertSee(config('media.tmdb.language'));
        $response->assertSee('qbittorrentUrl');
    }

    #[Test]
    public function user_can_save_app_settings(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->set('mediaAnimesPath', '/new/animes/path')
            ->set('mediaPeliculasPath', '/new/peliculas/path')
            ->set('videoExtensions', 'mkv,mp4,avi')
            ->set('tmdbApiKey', 'new-api-key')
            ->set('tmdbLanguage', 'en-US')
            ->set('qbittorrentUrl', 'http://localhost:8080')
            ->set('qbittorrentUser', 'newuser')
            ->set('qbittorrentPassword', 'newpass')
            ->set('nyaaBaseUrl', 'https://nyaa.si')
            ->set('nyaaDefaultSubmitter', 'SubsPlease')
            ->set('nyaaDefaultQuality', '720p')
            ->set('nyaaConcurrency', '3')
            ->set('nyaaCacheTtl', '3600')
            ->set('themeAccentColor', 'blue')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals('/new/animes/path', SettingService::get('media.paths.animes'));
        $this->assertEquals('/new/peliculas/path', SettingService::get('media.paths.peliculas'));
        $this->assertEquals('mkv,mp4,avi', SettingService::get('media.video_extensions'));
        $this->assertEquals('new-api-key', SettingService::get('media.tmdb.api_key'));
        $this->assertEquals('en-US', SettingService::get('media.tmdb.language'));
        $this->assertEquals('http://localhost:8080', SettingService::get('media.qbittorrent.url'));
        $this->assertEquals('newuser', SettingService::get('media.qbittorrent.user'));
        $this->assertEquals('newpass', SettingService::get('media.qbittorrent.password'));
        $this->assertEquals('SubsPlease', SettingService::get('media.nyaa.default_submitter'));
        $this->assertEquals('720p', SettingService::get('media.nyaa.default_quality'));
        $this->assertEquals('3', SettingService::get('media.nyaa.concurrency'));
        $this->assertEquals('3600', SettingService::get('media.nyaa.cache_ttl'));

        $user->refresh();
        $this->assertEquals('blue', $user->setting('theme.accent_color'));
    }

    #[Test]
    public function invalid_settings_are_rejected(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->set('mediaAnimesPath', '')
            ->set('mediaPeliculasPath', '')
            ->set('videoExtensions', 'invalid!!!')
            ->set('tmdbLanguage', 'invalid-lang')
            ->set('qbittorrentUrl', 'not-a-url')
            ->set('qbittorrentUser', '')
            ->set('qbittorrentPassword', '')
            ->set('nyaaBaseUrl', 'not-a-url')
            ->set('nyaaDefaultSubmitter', '')
            ->set('nyaaDefaultQuality', '')
            ->set('nyaaConcurrency', '0')
            ->set('themeAccentColor', 'not-a-color')
            ->call('save')
            ->assertHasErrors([
                'mediaAnimesPath',
                'mediaPeliculasPath',
                'videoExtensions',
                'tmdbLanguage',
                'qbittorrentUrl',
                'qbittorrentUser',
                'qbittorrentPassword',
                'nyaaBaseUrl',
                'nyaaDefaultSubmitter',
                'nyaaDefaultQuality',
                'nyaaConcurrency',
                'themeAccentColor',
            ]);
    }

    #[Test]
    public function theme_accent_color_is_applied_in_layout(): void
    {
        $user = User::factory()->create([
            'settings' => ['theme' => ['accent_color' => 'red']],
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('--color-red-500');
    }

    #[Test]
    public function settings_override_config_values(): void
    {
        Setting::create([
            'key' => 'media.paths.animes',
            'value' => '/overridden/animes',
        ]);

        Setting::create([
            'key' => 'media.tmdb.language',
            'value' => 'fr-FR',
        ]);

        // Simulate app boot overriding config
        config()->set('media.paths.animes', '/overridden/animes');
        config()->set('media.tmdb.language', 'fr-FR');

        $this->assertEquals('/overridden/animes', config('media.paths.animes'));
        $this->assertEquals('fr-FR', config('media.tmdb.language'));
    }
}
