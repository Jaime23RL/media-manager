<?php

namespace App\Livewire;

use App\Services\CompareService;
use App\Services\JikanService;
use App\Services\NamingService;
use App\Services\NyaaService;
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

    public string $seasonCreatedMessage = '';

    public array $existingSeasonFolders = [];

    public array $nyaaResults = [];

    public bool $searchingNyaa = false;

    public string $nyaaSearchMessage = '';

    public ?string $jikanName = null;

    public string $nyaaCustomQuery = '';

    public int $nyaaCustomSeason = 0;

    public int $nyaaCustomEpisode = 0;

    public array $openSeasons = [];

    public array $nyaaDebugLog = [];

    public array $customNames = [];

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
        $this->loadExistingSeasonFolders();
        $this->groupLocalFiles();
    }

    /**
     * Detect which Season X directories exist on disk.
     */
    private function loadExistingSeasonFolders(): void
    {
        $this->existingSeasonFolders = [];

        if (! $this->serie || ! isset($this->serie['path']) || ! File::isDirectory($this->serie['path'])) {
            return;
        }

        foreach (File::directories($this->serie['path']) as $directory) {
            $folderName = basename($directory);

            if (preg_match('/^Season\s+(\d+)$/i', $folderName, $matches)) {
                $this->existingSeasonFolders[] = (int) $matches[1];
            }
        }

        sort($this->existingSeasonFolders);
    }

    /**
     * Check if a season folder exists on disk.
     */
    public function seasonFolderExists(int $season): bool
    {
        return in_array($season, $this->existingSeasonFolders);
    }

    /**
     * Toggle a season's open/closed state.
     */
    public function toggleSeason(int $season): void
    {
        $key = array_search($season, $this->openSeasons);
        if ($key === false) {
            $this->openSeasons[] = $season;
        } else {
            unset($this->openSeasons[$key]);
            $this->openSeasons = array_values($this->openSeasons);
        }
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
        $this->customNames = $cached['custom_names'] ?? [];
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
            'custom_names' => $this->customNames,
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
        $this->customNames = [];
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

        // Load Jikan romaji name for nyaa search
        $this->loadJikanName();

        // Persist to cache
        $this->saveToCache();

        $this->loading = false;
    }

    /**
     * Load romaji name from Jikan (MyAnimeList) for better nyaa.si search results.
     */
    private function loadJikanName(): void
    {
        if (! $this->tmdb) {
            return;
        }

        try {
            $jikan = app(JikanService::class);
            $jikanResult = $jikan->searchByName($this->tmdb['name']);
            $this->jikanName = $jikanResult['title'] ?? null;

            $this->nyaaDebugLog[] = [
                'step' => 'jikan_search',
                'tmdb_name' => $this->tmdb['name'],
                'jikan_result' => $jikanResult,
                'jikan_name' => $this->jikanName,
            ];

            $this->nyaaDebugLog = array_merge($this->nyaaDebugLog, $jikan->getDebugLog());
        } catch (\Exception $e) {
            $this->jikanName = null;

            $this->nyaaDebugLog[] = [
                'step' => 'jikan_search_error',
                'tmdb_name' => $this->tmdb['name'],
                'error' => $e->getMessage(),
            ];
        }
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

    /**
     * Get seasons where the directory doesn't exist on disk.
     */
    public function getMissingSeasons(): array
    {
        if (! $this->episodesBySeason) {
            return [];
        }

        $missingSeasons = [];

        foreach ($this->episodesBySeason as $season => $episodes) {
            if ($season == 0) {
                continue;
            }

            if (! $this->seasonFolderExists($season)) {
                $missingSeasons[] = $season;
            }
        }

        return $missingSeasons;
    }

    /**
     * Create a season folder for the given season number.
     */
    public function createSeasonFolder(int $season): void
    {
        if (! $this->serie || ! isset($this->serie['path'])) {
            return;
        }

        $scanner = app(ScannerService::class);
        $created = $scanner->createSeasonFolder($this->serie['path'], $season);

        if ($created) {
            $this->seasonCreatedMessage = "Season {$season} folder created";

            // Refresh local files to show the new empty season folder
            $this->loadSerie();
        } else {
            $this->seasonCreatedMessage = "Season {$season} folder already exists";
        }
    }

    /**
     * Create all missing season folders at once.
     */
    public function createAllMissingSeasons(): void
    {
        if (! $this->serie || ! isset($this->serie['path'])) {
            return;
        }

        $missingSeasons = $this->getMissingSeasons();

        if (empty($missingSeasons)) {
            return;
        }

        $scanner = app(ScannerService::class);
        $createdCount = 0;

        foreach ($missingSeasons as $season) {
            if ($scanner->createSeasonFolder($this->serie['path'], $season)) {
                $createdCount++;
            }
        }

        if ($createdCount > 0) {
            $this->seasonCreatedMessage = "{$createdCount} season folder(s) created";
            $this->loadSerie();
        } else {
            $this->seasonCreatedMessage = 'All season folders already exist';
        }
    }

    /**
     * Search nyaa.si for a specific missing episode using dual names.
     */
    public function searchNyaaForEpisode(int $season, int $episode): void
    {
        if (! $this->serie || ! $this->tmdb) {
            return;
        }

        $this->nyaaSearchMessage = '';
        $this->nyaaDebugLog = [];

        $customName = $this->customNames[$season] ?? null;
        $episodePadded = str_pad((string) $episode, 2, '0', STR_PAD_LEFT);

        $names = array_filter([
            $this->tmdb['name'],
            $this->jikanName,
            $customName ? "{$customName} - {$episodePadded}" : null,
        ]);

        $this->nyaaDebugLog[] = [
            'step' => 'search_episode_start',
            'names' => $names,
            'season' => $season,
            'episode' => $episode,
            'tmdb_name' => $this->tmdb['name'],
            'jikan_name' => $this->jikanName,
            'custom_name' => $customName,
            'custom_name_with_ep' => $customName ? "{$customName} - {$episodePadded}" : null,
        ];

        $nyaa = app(NyaaService::class);
        $results = $nyaa->searchEpisode($names, $episode);

        $this->nyaaDebugLog[] = [
            'step' => 'search_episode_results',
            'total_results' => count($results),
            'results' => array_map(fn ($r) => ['title' => $r['title'], 'seeders' => $r['seeders']], $results),
        ];

        $this->nyaaDebugLog = array_merge($this->nyaaDebugLog, $nyaa->getDebugLog());

        $this->nyaaResults["{$season}_{$episode}"] = $results;

        if (empty($results)) {
            $this->nyaaSearchMessage = 'No results found for E'.str_pad((string) $episode, 2, '0', STR_PAD_LEFT).'. Try a custom search below.';
            $this->nyaaCustomSeason = $season;
            $this->nyaaCustomEpisode = $episode;
        } else {
            $this->nyaaSearchMessage = count($results).' result(s) found for E'.str_pad((string) $episode, 2, '0', STR_PAD_LEFT);
        }
    }

    /**
     * Search nyaa.si for all missing episodes in a season using dual names.
     */
    public function searchNyaaForSeason(int $season): void
    {
        if (! $this->serie || ! $this->tmdb || ! isset($this->episodesBySeason[$season])) {
            return;
        }

        $this->searchingNyaa = true;
        $this->nyaaSearchMessage = '';
        $this->nyaaDebugLog = [];

        $missingEpisodes = collect($this->episodesBySeason[$season])
            ->where('status', 'missing')
            ->pluck('episode')
            ->toArray();

        if (empty($missingEpisodes)) {
            $this->nyaaSearchMessage = "No missing episodes in Season {$season}";
            $this->searchingNyaa = false;

            return;
        }

        $names = array_filter([
            $this->tmdb['name'],
            $this->jikanName,
            $this->customNames[$season] ?? null,
        ]);

        $this->nyaaDebugLog[] = [
            'step' => 'search_season_start',
            'names' => $names,
            'season' => $season,
            'missing_episodes' => $missingEpisodes,
            'tmdb_name' => $this->tmdb['name'],
            'jikan_name' => $this->jikanName,
            'custom_name' => $this->customNames[$season] ?? null,
        ];

        $nyaa = app(NyaaService::class);
        $results = $nyaa->searchMultipleEpisodes($names, $missingEpisodes);

        foreach ($results as $ep => $torrents) {
            $this->nyaaResults["{$season}_{$ep}"] = $torrents;
        }

        $totalResults = array_sum(array_map('count', $results));
        $this->nyaaSearchMessage = "{$totalResults} result(s) found for ".count($missingEpisodes)." episode(s) in Season {$season}";

        $this->nyaaDebugLog[] = [
            'step' => 'search_season_results',
            'total_results' => $totalResults,
            'per_episode' => array_map(fn ($ep, $t) => ['episode' => $ep, 'count' => count($t)], array_keys($results), $results),
        ];

        $this->nyaaDebugLog = array_merge($this->nyaaDebugLog, $nyaa->getDebugLog());

        $this->searchingNyaa = false;

        if ($totalResults === 0) {
            $this->nyaaSearchMessage = "No results found for Season {$season}. Try a custom search below.";
            $this->nyaaCustomSeason = $season;
            $this->nyaaCustomEpisode = 0;
        }
    }

    /**
     * Search nyaa.si with a custom query string.
     */
    public function searchNyaaCustom(): void
    {
        if ($this->nyaaCustomQuery === '') {
            return;
        }

        $this->nyaaSearchMessage = '';
        $this->nyaaDebugLog = [];

        $this->nyaaDebugLog[] = [
            'step' => 'custom_search_start',
            'query' => $this->nyaaCustomQuery,
            'season' => $this->nyaaCustomSeason,
            'episode' => $this->nyaaCustomEpisode,
        ];

        $nyaa = app(NyaaService::class);
        $results = $nyaa->searchCustom($this->nyaaCustomQuery);

        $this->nyaaDebugLog[] = [
            'step' => 'custom_search_results',
            'total_results' => count($results),
            'results' => array_map(fn ($r) => ['title' => $r['title'], 'seeders' => $r['seeders']], $results),
        ];

        $this->nyaaDebugLog = array_merge($this->nyaaDebugLog, $nyaa->getDebugLog());

        $key = "{$this->nyaaCustomSeason}_{$this->nyaaCustomEpisode}";
        $this->nyaaResults[$key] = $results;

        if (empty($results)) {
            $this->nyaaSearchMessage = 'No results found for "'.$this->nyaaCustomQuery.'"';
        } else {
            $this->nyaaSearchMessage = count($results).' result(s) found for "'.$this->nyaaCustomQuery.'"';

            // Save custom name for this season for future searches
            $this->customNames[$this->nyaaCustomSeason] = $this->nyaaCustomQuery;
            $this->saveToCache();
        }
    }

    /**
     * Reset the custom name for a season.
     */
    public function resetCustomName(int $season): void
    {
        unset($this->customNames[$season]);
        $this->customNames = array_values($this->customNames);
        $this->saveToCache();
    }

    public function render()
    {
        return view('livewire.serie-detail-page');
    }
}
