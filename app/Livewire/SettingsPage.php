<?php

namespace App\Livewire;

use App\Services\SettingService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SettingsPage extends Component
{
    public string $mediaAnimesPath = '';

    public string $mediaPeliculasPath = '';

    public string $videoExtensions = '';

    public string $tmdbApiKey = '';

    public string $tmdbLanguage = '';

    public string $qbittorrentUrl = '';

    public string $qbittorrentUser = '';

    public string $qbittorrentPassword = '';

    public string $nyaaBaseUrl = '';

    public string $nyaaDefaultSubmitter = '';

    public string $nyaaDefaultQuality = '';

    public string $nyaaConcurrency = '';

    public string $nyaaCacheTtl = '';

    public string $themeAccentColor = '';

    public function mount(): void
    {
        $this->mediaAnimesPath = SettingService::get('media.paths.animes', config('media.paths.animes'));
        $this->mediaPeliculasPath = SettingService::get('media.paths.peliculas', config('media.paths.peliculas'));
        $this->videoExtensions = SettingService::get('media.video_extensions', implode(',', config('media.video_extensions')));

        $this->tmdbApiKey = SettingService::get('media.tmdb.api_key', config('media.tmdb.api_key'));
        $this->tmdbLanguage = SettingService::get('media.tmdb.language', config('media.tmdb.language'));

        $this->qbittorrentUrl = SettingService::get('media.qbittorrent.url', config('media.qbittorrent.url'));
        $this->qbittorrentUser = SettingService::get('media.qbittorrent.user', config('media.qbittorrent.user'));
        $this->qbittorrentPassword = SettingService::get('media.qbittorrent.password', config('media.qbittorrent.password'));

        $this->nyaaBaseUrl = SettingService::get('media.nyaa.base_url', config('media.nyaa.base_url'));
        $this->nyaaDefaultSubmitter = SettingService::get('media.nyaa.default_submitter', config('media.nyaa.default_submitter'));
        $this->nyaaDefaultQuality = SettingService::get('media.nyaa.default_quality', config('media.nyaa.default_quality'));
        $this->nyaaConcurrency = (string) SettingService::get('media.nyaa.concurrency', config('media.nyaa.concurrency'));
        $this->nyaaCacheTtl = (string) SettingService::get('media.nyaa.cache_ttl', config('media.nyaa.cache_ttl'));

        $this->themeAccentColor = Auth::user()->setting('theme.accent_color', 'neutral');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'mediaAnimesPath' => ['required', 'string'],
            'mediaPeliculasPath' => ['required', 'string'],
            'videoExtensions' => ['required', 'string', 'regex:/^[a-z0-9]+(,[a-z0-9]+)*$/i'],
            'tmdbApiKey' => ['nullable', 'string'],
            'tmdbLanguage' => ['required', 'string', 'regex:/^[a-z]{2}(-[A-Z]{2})?$/'],
            'qbittorrentUrl' => ['required', 'string', 'url'],
            'qbittorrentUser' => ['required', 'string'],
            'qbittorrentPassword' => ['required', 'string'],
            'nyaaBaseUrl' => ['required', 'string', 'url'],
            'nyaaDefaultSubmitter' => ['required', 'string'],
            'nyaaDefaultQuality' => ['required', 'string'],
            'nyaaConcurrency' => ['required', 'integer', 'min:1', 'max:20'],
            'nyaaCacheTtl' => ['required', 'integer', 'min:0'],
            'themeAccentColor' => ['required', 'string', 'in:slate,gray,zinc,neutral,stone,mauve,olive,mist,taupe,red,orange,amber,yellow,lime,green,emerald,teal,cyan,sky,blue,indigo,violet,purple,fuchsia,pink,rose'],
        ]);

        SettingService::set('media.paths.animes', $validated['mediaAnimesPath']);
        SettingService::set('media.paths.peliculas', $validated['mediaPeliculasPath']);
        SettingService::set('media.video_extensions', $validated['videoExtensions']);

        SettingService::set('media.tmdb.api_key', $validated['tmdbApiKey']);
        SettingService::set('media.tmdb.language', $validated['tmdbLanguage']);

        SettingService::set('media.qbittorrent.url', $validated['qbittorrentUrl']);
        SettingService::set('media.qbittorrent.user', $validated['qbittorrentUser']);
        SettingService::set('media.qbittorrent.password', $validated['qbittorrentPassword']);

        SettingService::set('media.nyaa.base_url', $validated['nyaaBaseUrl']);
        SettingService::set('media.nyaa.default_submitter', $validated['nyaaDefaultSubmitter']);
        SettingService::set('media.nyaa.default_quality', $validated['nyaaDefaultQuality']);
        SettingService::set('media.nyaa.concurrency', $validated['nyaaConcurrency']);
        SettingService::set('media.nyaa.cache_ttl', $validated['nyaaCacheTtl']);

        Auth::user()->setSetting('theme.accent_color', $validated['themeAccentColor']);

        Flux::toast(variant: 'success', text: __('Settings saved.'));

        $this->dispatch('theme-changed', color: $validated['themeAccentColor']);
    }

    public function selectColor(string $color): void
    {
        $this->themeAccentColor = $color;
        $this->dispatch('theme-changed', color: $color);
    }

    #[Computed]
    public function accentColors(): array
    {
        return [
            'slate' => 'Slate',
            'gray' => 'Gray',
            'zinc' => 'Zinc',
            'neutral' => 'Neutral',
            'stone' => 'Stone',
            'mauve' => 'Mauve',
            'olive' => 'Olive',
            'mist' => 'Mist',
            'taupe' => 'Taupe',
            'red' => 'Red',
            'orange' => 'Orange',
            'amber' => 'Amber',
            'yellow' => 'Yellow',
            'lime' => 'Lime',
            'green' => 'Green',
            'emerald' => 'Emerald',
            'teal' => 'Teal',
            'cyan' => 'Cyan',
            'sky' => 'Sky',
            'blue' => 'Blue',
            'indigo' => 'Indigo',
            'violet' => 'Violet',
            'purple' => 'Purple',
            'fuchsia' => 'Fuchsia',
            'pink' => 'Pink',
            'rose' => 'Rose',
        ];
    }
}
