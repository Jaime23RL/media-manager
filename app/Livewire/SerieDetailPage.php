<?php

namespace App\Livewire;

use App\Services\CompareService;
use App\Services\NamingService;
use App\Services\ScannerService;
use App\Services\TmdbService;
use Illuminate\Support\Facades\File;
use Livewire\Component;

class SerieDetailPage extends Component
{
    public $id;

    public $serie = null;

    public $tmdb = null;

    public $comparison = null;

    public $episodesBySeason = [];

    public $localFilesBySeason = [];

    public $loading = false;

    public function mount($id): void
    {
        $this->id = $id;
        $this->loadSerie();
        $this->loadFromCache();
    }

    public function loadSerie(): void
    {
        $scanner = app(ScannerService::class);
        $data = $scanner->loadScan('animes');

        if (! $data || ! isset($data['series'][$this->id])) {
            return;
        }

        $this->serie = $data['series'][$this->id];
        $this->groupLocalFiles();
    }

    /**
     * Group local files by season using NamingService.
     */
    private function groupLocalFiles(): void
    {
        if (! $this->serie) {
            return;
        }

        $namingService = app(NamingService::class);

        foreach ($this->serie['files'] as $file) {
            $parsed = $namingService->parse($file);
            $season = $parsed ? $parsed['season'] : 0;
            $episode = $parsed ? $parsed['episode'] : null;

            $this->localFilesBySeason[$season][] = [
                'filename' => $file,
                'episode' => $episode,
                'parsed' => $parsed !== null,
            ];
        }

        ksort($this->localFilesBySeason);
    }

    /**
     * Load cached TMDB lookup if available.
     */
    private function loadFromCache(): void
    {
        $cachePath = $this->getCachePath();

        if (! file_exists($cachePath)) {
            return;
        }

        $content = file_get_contents($cachePath);
        $cached = json_decode($content, true);

        if (! $cached) {
            return;
        }

        $this->tmdb = $cached['tmdb'] ?? null;
        $this->comparison = $cached['comparison'] ?? null;
        $this->episodesBySeason = $cached['episodes_by_season'] ?? [];
    }

    /**
     * Save TMDB lookup results to cache.
     */
    private function saveToCache(): void
    {
        $cacheDir = config('media.cache_path');

        if (! File::isDirectory($cacheDir)) {
            File::makeDirectory($cacheDir, 0755, true);
        }

        $data = [
            'tmdb' => $this->tmdb,
            'comparison' => $this->comparison,
            'episodes_by_season' => $this->episodesBySeason,
            'cached_at' => now()->toISOString(),
        ];

        file_put_contents($this->getCachePath(), json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Get cache file path for this series.
     */
    private function getCachePath(): string
    {
        return config('media.cache_path').'/serie_'.md5($this->serie['name'] ?? $this->id).'.json';
    }

    /**
     * Clear cached TMDB data for this series.
     */
    public function clearCache(): void
    {
        $path = $this->getCachePath();

        if (file_exists($path)) {
            unlink($path);
        }

        $this->tmdb = null;
        $this->comparison = null;
        $this->episodesBySeason = [];
    }

    public function lookupTmdb(): void
    {
        if (! $this->serie) {
            return;
        }

        $this->loading = true;

        $tmdb = app(TmdbService::class);
        $namingService = app(NamingService::class);

        // Search TMDB
        $normalizedName = $namingService->normalizeSerieName($this->serie['name']);
        $searchResult = $tmdb->searchTv($normalizedName);

        if (! $searchResult) {
            $this->loading = false;

            return;
        }

        $this->tmdb = [
            'tmdb_id' => $searchResult['id'],
            'name' => $searchResult['name'] ?? $searchResult['original_name'] ?? $this->serie['name'],
            'overview' => $searchResult['overview'] ?? '',
            'poster_path' => $searchResult['poster_path'] ?? null,
            'first_air_date' => $searchResult['first_air_date'] ?? null,
        ];

        // Get all episodes and compare
        $tmdbEpisodes = $tmdb->getAllEpisodes($this->tmdb['tmdb_id']);
        $compareService = app(CompareService::class);
        $this->comparison = $compareService->compare($this->serie['files'], $tmdbEpisodes);

        // Group episodes by season for display
        $this->episodesBySeason = $this->groupEpisodes($tmdbEpisodes, $this->comparison);

        // Persist to cache
        $this->saveToCache();

        $this->loading = false;
    }

    /**
     * Group TMDB episodes by season, marking each as have/missing/upcoming.
     */
    private function groupEpisodes(array $tmdbEpisodes, array $comparison): array
    {
        $haveMap = [];
        foreach ($comparison['have'] as $ep) {
            $haveMap[$ep['season']][$ep['episode']] = $ep;
        }

        $missingMap = [];
        foreach ($comparison['missing'] as $ep) {
            $missingMap[$ep['season']][$ep['episode']] = $ep;
        }

        $upcomingMap = [];
        foreach ($comparison['upcoming'] as $ep) {
            $upcomingMap[$ep['season']][$ep['episode']] = $ep;
        }

        $grouped = [];
        foreach ($tmdbEpisodes as $ep) {
            $season = $ep['season'];
            $episode = $ep['episode'];

            $status = 'upcoming';
            $filename = null;

            if (isset($haveMap[$season][$episode])) {
                $status = 'have';
                $filename = $haveMap[$season][$episode]['filename'];
            } elseif (isset($missingMap[$season][$episode])) {
                $status = 'missing';
            }

            $grouped[$season][] = [
                'episode' => $episode,
                'name' => $ep['name'] ?? '',
                'air_date' => $ep['air_date'] ?? null,
                'status' => $status,
                'filename' => $filename,
            ];
        }

        ksort($grouped);

        return $grouped;
    }

    public function render()
    {
        return view('livewire.serie-detail-page');
    }
}
