<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class TmdbService
{
    private string $apiKey;

    private string $language;

    private string $baseUrl;

    private string $cachePath;

    public function __construct()
    {
        $this->apiKey = config('media.tmdb.api_key');
        $this->language = config('media.tmdb.language');
        $this->baseUrl = config('media.tmdb.base_url');
        $this->cachePath = config('media.cache_path');
    }

    /**
     * Search for a TV series by name.
     *
     * @return array|null First matching result or null
     */
    public function searchTv(string $query): ?array
    {
        $results = $this->searchTvAll($query);

        return $results[0] ?? null;
    }

    /**
     * Search for a TV series by name and return all results.
     *
     * @return array List of matching results
     */
    public function searchTvAll(string $query): array
    {
        $response = Http::withToken($this->apiKey)
            ->get("{$this->baseUrl}/search/tv", [
                'query' => $query,
                'language' => $this->language,
            ]);

        if ($response->failed()) {
            return [];
        }

        return $response->json('results', []);
    }

    /**
     * Get full TV series details including seasons.
     */
    public function getTvDetails(int $tmdbId): ?array
    {
        $cacheKey = "tv_{$tmdbId}";
        $cached = $this->getFromCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $response = Http::withToken($this->apiKey)
            ->get("{$this->baseUrl}/tv/{$tmdbId}", [
                'language' => $this->language,
            ]);

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();
        $this->saveToCache($cacheKey, $data);

        return $data;
    }

    /**
     * Get season details with episodes list.
     */
    public function getSeason(int $tmdbId, int $seasonNumber): ?array
    {
        $cacheKey = "tv_{$tmdbId}_season_{$seasonNumber}";
        $cached = $this->getFromCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $response = Http::withToken($this->apiKey)
            ->get("{$this->baseUrl}/tv/{$tmdbId}/season/{$seasonNumber}", [
                'language' => $this->language,
            ]);

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();
        $this->saveToCache($cacheKey, $data);

        return $data;
    }

    /**
     * Get all episodes for a series (across all seasons).
     *
     * @return array Flattened list of episodes [['season' => 1, 'episode' => 1, 'name' => '...'], ...]
     */
    public function getAllEpisodes(int $tmdbId): array
    {
        $details = $this->getTvDetails($tmdbId);
        if (! $details) {
            return [];
        }

        $episodes = [];
        $seasons = $details['seasons'] ?? [];

        foreach ($seasons as $season) {
            $seasonNumber = $season['season_number'];
            if ($seasonNumber === 0) {
                continue; // Skip specials
            }

            $seasonData = $this->getSeason($tmdbId, $seasonNumber);
            if (! $seasonData) {
                continue;
            }

            foreach (($seasonData['episodes'] ?? []) as $episode) {
                $episodes[] = [
                    'season' => $seasonNumber,
                    'episode' => $episode['episode_number'],
                    'name' => $episode['name'] ?? '',
                    'air_date' => $episode['air_date'] ?? null,
                ];
            }
        }

        return $episodes;
    }

    /**
     * Get series info with episode counts for a list of series names.
     *
     * @param  array  $seriesNames  Array of series names to look up
     * @return array Keyed by original name => ['tmdb_id', 'name', 'total_episodes', 'seasons']
     */
    public function lookupMultiple(array $seriesNames): array
    {
        $results = [];

        foreach ($seriesNames as $name) {
            $searchResult = $this->searchTv($name);
            if ($searchResult) {
                $results[$name] = [
                    'tmdb_id' => $searchResult['id'],
                    'name' => $searchResult['name'] ?? $searchResult['original_name'] ?? $name,
                    'overview' => $searchResult['overview'] ?? '',
                    'poster_path' => $searchResult['poster_path'] ?? null,
                    'first_air_date' => $searchResult['first_air_date'] ?? null,
                ];
            } else {
                $results[$name] = null;
            }
        }

        return $results;
    }

    /**
     * Read from JSON cache.
     */
    private function getFromCache(string $key): ?array
    {
        $filePath = $this->cachePath."/{$key}.json";

        if (! file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);
        $data = json_decode($content, true);

        return $data ?? null;
    }

    /**
     * Write to JSON cache.
     */
    private function saveToCache(string $key, array $data): void
    {
        if (! File::isDirectory($this->cachePath)) {
            File::makeDirectory($this->cachePath, 0755, true);
        }

        $filePath = $this->cachePath."/{$key}.json";
        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Clear all cached TMDB data.
     */
    public function clearCache(): void
    {
        if (File::isDirectory($this->cachePath)) {
            File::cleanDirectory($this->cachePath);
        }
    }
}
