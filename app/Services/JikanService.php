<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class JikanService
{
    private string $baseUrl = 'https://api.jikan.moe/v4';

    private string $cachePath;

    public function __construct()
    {
        $this->cachePath = storage_path('app/cache/jikan');
    }

    /**
     * Search for an anime by name and return romaji/title info.
     */
    public function searchByName(string $query): ?array
    {
        $cacheKey = 'search_'.md5(strtolower($query));
        $cached = $this->getFromCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $response = Http::get("{$this->baseUrl}/anime", [
            'q' => $query,
            'limit' => 5,
        ]);

        if ($response->failed()) {
            return null;
        }

        $results = $response->json('data', []);

        if (empty($results)) {
            return null;
        }

        // Find best match by comparing titles
        $bestMatch = null;
        $queryLower = strtolower($query);

        foreach ($results as $result) {
            $titles = array_map('strtolower', array_column($result['titles'] ?? [], 'title'));
            $titles[] = strtolower($result['title'] ?? '');
            $titles[] = strtolower($result['title_english'] ?? '');

            foreach ($titles as $title) {
                if ($title === $queryLower || str_contains($title, $queryLower) || str_contains($queryLower, $title)) {
                    $bestMatch = $result;
                    break 2;
                }
            }
        }

        // Fallback to first result if no exact match
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
