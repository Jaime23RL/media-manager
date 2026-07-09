<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class JikanService
{
    private string $baseUrl = 'https://api.jikan.moe/v4';

    private string $cachePath;

    private array $debugLog = [];

    public function __construct()
    {
        $this->cachePath = storage_path('app/cache/jikan');
    }

    /**
     * Get the debug log for this request.
     */
    public function getDebugLog(): array
    {
        return $this->debugLog;
    }

    /**
     * Search for an anime by name and return romaji/title info.
     */
    public function searchByName(string $query): ?array
    {
        $this->debugLog = [];

        $cacheKey = 'search_'.md5(strtolower($query));
        $cached = $this->getFromCache($cacheKey);
        if ($cached !== null) {
            $this->debugLog[] = [
                'step' => 'jikan_cache_hit',
                'query' => $query,
                'cached' => $cached,
            ];

            return $cached;
        }

        $this->debugLog[] = [
            'step' => 'jikan_api_call',
            'query' => $query,
            'url' => "{$this->baseUrl}/anime?q=".urlencode($query).'&limit=5',
        ];

        $response = Http::retry(2, 1000)->get("{$this->baseUrl}/anime", [
            'q' => $query,
            'limit' => 5,
        ]);

        if ($response->failed()) {
            $this->debugLog[] = [
                'step' => 'jikan_api_error',
                'query' => $query,
                'status' => $response->status(),
            ];

            return null;
        }

        $results = $response->json('data', []);

        if (empty($results)) {
            $this->debugLog[] = [
                'step' => 'jikan_no_results',
                'query' => $query,
            ];

            return null;
        }

        $this->debugLog[] = [
            'step' => 'jikan_raw_results',
            'query' => $query,
            'count' => count($results),
            'titles' => array_map(fn ($r) => [
                'title' => $r['title'] ?? '',
                'title_english' => $r['title_english'] ?? '',
            ], $results),
        ];

        // Find best match by comparing titles
        $bestMatch = null;
        $matchedTitle = '';
        $queryLower = strtolower($query);
        $queryWords = preg_split('/\s+/', $queryLower);

        foreach ($results as $result) {
            $titles = array_map('strtolower', array_column($result['titles'] ?? [], 'title'));
            $titles[] = strtolower($result['title'] ?? '');
            $titles[] = strtolower($result['title_english'] ?? '');

            foreach ($titles as $title) {
                if ($title === $queryLower || str_contains($title, $queryLower) || str_contains($queryLower, $title)) {
                    $bestMatch = $result;
                    $matchedTitle = $title;
                    break 2;
                }
            }

            // Fallback: check if at least 60% of query words appear in any title
            if (! $bestMatch) {
                foreach ($titles as $title) {
                    if ($title === '') {
                        continue;
                    }
                    $matchingWords = count(array_filter($queryWords, fn ($w) => str_contains($title, $w)));
                    $matchRatio = $matchingWords / count($queryWords);
                    if ($matchRatio >= 0.6) {
                        $bestMatch = $result;
                        $matchedTitle = $title;
                        break 2;
                    }
                }
            }
        }

        $this->debugLog[] = [
            'step' => 'jikan_match_result',
            'query' => $query,
            'best_match_found' => $bestMatch !== null,
            'matched_title' => $matchedTitle ?: null,
            'fallback_used' => $bestMatch === null,
        ];

        // Fallback to first result if no match
        if (! $bestMatch) {
            $bestMatch = $results[0];
        }

        $data = [
            'mal_id' => $bestMatch['mal_id'],
            'title' => $bestMatch['title'] ?? '', // Romaji
            'title_english' => $bestMatch['title_english'] ?? '',
            'title_japanese' => $bestMatch['title_japanese'] ?? '',
        ];

        $this->saveToCache($cacheKey, $data);

        return $data;
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

        return json_decode($content, true) ?? null;
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
}
