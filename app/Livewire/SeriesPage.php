<?php

namespace App\Livewire;

use App\Services\CompareService;
use App\Services\NamingService;
use App\Services\ScannerService;
use App\Services\TmdbService;
use Illuminate\Support\Facades\File;
use Livewire\Component;

class SeriesPage extends Component
{
    public $series = [];

    public $loading = false;

    public $searching = false;

    public $lastScanTime = null;

    public function boot(): void
    {
        $this->loadFromCache();
    }

    public function loadFromCache(): void
    {
        $scanner = app(ScannerService::class);
        $data = $scanner->loadScan('animes');

        if ($data) {
            $this->series = $data['series'] ?? [];
            $this->lastScanTime = $data['scanned_at'] ?? null;
        }
    }

    public function lookupTmdb(): void
    {
        if (empty($this->series)) {
            return;
        }

        $this->searching = true;

        $tmdb = app(TmdbService::class);
        $namingService = app(NamingService::class);
        $compareService = app(CompareService::class);
        $today = date('Y-m-d');

        foreach ($this->series as &$serie) {
            $normalizedName = $namingService->normalizeSerieName($serie['name']);
            $searchResult = $tmdb->searchTv($normalizedName);

            if (! $searchResult) {
                $serie['tmdb'] = null;

                continue;
            }

            $tmdbInfo = [
                'tmdb_id' => $searchResult['id'],
                'name' => $searchResult['name'] ?? $searchResult['original_name'] ?? $serie['name'],
                'overview' => $searchResult['overview'] ?? '',
                'poster_path' => $searchResult['poster_path'] ?? null,
                'first_air_date' => $searchResult['first_air_date'] ?? null,
            ];

            $serie['tmdb'] = $tmdbInfo;

            // Get full episodes and comparison
            $tmdbEpisodes = $tmdb->getAllEpisodes($tmdbInfo['tmdb_id']);
            $comparison = $compareService->compare($serie['files'], $tmdbEpisodes);

            // Count for display
            $serie['total_episodes'] = count($tmdbEpisodes);
            $serie['have_count'] = count($comparison['have']);
            $serie['missing_count'] = count($comparison['missing']);
            $serie['upcoming_count'] = count($comparison['upcoming']);

            // Group episodes by season for detail page
            $episodesBySeason = $this->groupEpisodes($tmdbEpisodes, $comparison);

            // Save per-series cache so SerieDetailPage loads instantly
            $this->saveSerieCache($serie['name'], $tmdbInfo, $comparison, $episodesBySeason);
        }
        unset($serie);

        $this->searching = false;
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

    /**
     * Save TMDB data for a single series to cache.
     */
    private function saveSerieCache(string $serieName, array $tmdb, array $comparison, array $episodesBySeason): void
    {
        $cacheDir = config('media.cache_path');

        if (! File::isDirectory($cacheDir)) {
            File::makeDirectory($cacheDir, 0755, true);
        }

        $data = [
            'tmdb' => $tmdb,
            'comparison' => $comparison,
            'episodes_by_season' => $episodesBySeason,
            'cached_at' => now()->toISOString(),
        ];

        $filePath = $cacheDir.'/serie_'.md5($serieName).'.json';
        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function render()
    {
        return view('livewire.series-page');
    }
}
