<?php

namespace App\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;

class NyaaService
{
    private string $baseUrl;

    private string $defaultSubmitter;

    private string $defaultQuality;

    private int $concurrency;

    private int $cacheTtl;

    private array $debugLog = [];

    public function __construct()
    {
        $this->baseUrl = config('media.nyaa.base_url');
        $this->defaultSubmitter = config('media.nyaa.default_submitter');
        $this->defaultQuality = config('media.nyaa.default_quality');
        $this->concurrency = config('media.nyaa.concurrency');
        $this->cacheTtl = config('media.nyaa.cache_ttl');
    }

    /**
     * Get the debug log for this request.
     */
    public function getDebugLog(): array
    {
        return $this->debugLog;
    }

    /**
     * Search for a specific episode on nyaa.si using multiple names.
     *
     * @param  array<string>  $seriesNames  List of names to search (English, romaji, etc.)
     * @return array List of matching torrents
     */
    public function searchEpisode(array $seriesNames, int $episodeNumber): array
    {
        $this->debugLog = [];
        $allResults = [];

        foreach ($seriesNames as $name) {
            $query = sprintf('%s - %02d', $name, $episodeNumber);

            $this->debugLog[] = [
                'step' => 'nyaa_query',
                'name' => $name,
                'query' => $query,
                'url' => "{$this->baseUrl}?page=rss&q=".urlencode($query).'&c=1_2',
            ];

            $cacheKey = 'nyaa_'.md5($query);
            $cached = $this->getFromCache($cacheKey);
            if ($cached !== null) {
                $this->debugLog[] = [
                    'step' => 'cache_hit',
                    'query' => $query,
                    'cached_count' => count($cached),
                ];
                $allResults = array_merge($allResults, $cached);

                continue;
            }

            $rawResults = $this->fetchRss($query);
            $this->debugLog[] = [
                'step' => 'raw_results',
                'query' => $query,
                'raw_count' => count($rawResults),
                'titles' => array_map(fn ($r) => $r['title'], $rawResults),
            ];

            $filteredSubmitter = $this->filterBySubmitter($rawResults);
            $this->debugLog[] = [
                'step' => 'after_submitter_filter',
                'query' => $query,
                'count' => count($filteredSubmitter),
                'submitter' => $this->defaultSubmitter,
            ];

            $filteredQuality = $this->filterByQuality($filteredSubmitter);
            $this->debugLog[] = [
                'step' => 'after_quality_filter',
                'query' => $query,
                'count' => count($filteredQuality),
                'quality' => $this->defaultQuality,
            ];

            $results = $this->excludeBatches($filteredQuality);
            $this->debugLog[] = [
                'step' => 'after_batch_filter',
                'query' => $query,
                'count' => count($results),
            ];

            $this->saveToCache($cacheKey, $results);

            $allResults = array_merge($allResults, $results);
        }

        $allResults = $this->deduplicateByInfoHash($allResults);
        $allResults = $this->sortBySeeders($allResults);

        return $allResults;
    }

    /**
     * Search with a custom query string (user-provided).
     *
     * @return array List of matching torrents
     */
    public function searchCustom(string $query): array
    {
        $this->debugLog = [];

        $this->debugLog[] = [
            'step' => 'custom_query',
            'query' => $query,
            'url' => "{$this->baseUrl}?page=rss&q=".urlencode($query).'&c=1_2',
        ];

        $cacheKey = 'nyaa_custom_'.md5($query);
        $cached = $this->getFromCache($cacheKey);
        if ($cached !== null) {
            $this->debugLog[] = [
                'step' => 'cache_hit',
                'query' => $query,
                'cached_count' => count($cached),
            ];

            return $cached;
        }

        $rawResults = $this->fetchRss($query);
        $this->debugLog[] = [
            'step' => 'raw_results',
            'query' => $query,
            'raw_count' => count($rawResults),
            'titles' => array_map(fn ($r) => $r['title'], $rawResults),
        ];

        $filteredSubmitter = $this->filterBySubmitter($rawResults);
        $this->debugLog[] = [
            'step' => 'after_submitter_filter',
            'query' => $query,
            'count' => count($filteredSubmitter),
            'submitter' => $this->defaultSubmitter,
        ];

        $filteredQuality = $this->filterByQuality($filteredSubmitter);
        $this->debugLog[] = [
            'step' => 'after_quality_filter',
            'query' => $query,
            'count' => count($filteredQuality),
            'quality' => $this->defaultQuality,
        ];

        $results = $this->excludeBatches($filteredQuality);
        $this->debugLog[] = [
            'step' => 'after_batch_filter',
            'query' => $query,
            'count' => count($results),
        ];

        $results = $this->sortBySeeders($results);

        $this->saveToCache($cacheKey, $results);

        return $results;
    }

    /**
     * Search for multiple episodes concurrently using HTTP pool.
     *
     * @param  array<string>  $seriesNames  List of names to search
     * @param  array<int>  $episodeNumbers
     * @return array<int, array> Keyed by episode number
     */
    public function searchMultipleEpisodes(array $seriesNames, array $episodeNumbers): array
    {
        // Build queries for each name + episode combination
        $queries = [];
        foreach ($seriesNames as $name) {
            foreach ($episodeNumbers as $ep) {
                $key = "{$ep}_".md5($name);
                $queries[$key] = [
                    'ep' => $ep,
                    'query' => sprintf('%s - %02d', $name, $ep),
                ];
            }
        }

        $responses = Http::pool(fn (Pool $pool) => array_map(
            fn ($key, $q) => $pool->as($key)->timeout(15)->get($this->baseUrl, [
                'page' => 'rss',
                'q' => $q['query'],
                'c' => '1_2',
            ]),
            array_keys($queries),
            $queries
        ), $this->concurrency);

        // Group results by episode
        $results = [];
        foreach ($episodeNumbers as $ep) {
            $results[$ep] = [];
        }

        foreach ($queries as $key => $q) {
            $ep = $q['ep'];
            $response = $responses[$key] ?? null;
            if ($response && $response->successful()) {
                $torrents = $this->parseRssResponse($response->body());
                $torrents = $this->filterBySubmitter($torrents);
                $torrents = $this->filterByQuality($torrents);
                $torrents = $this->excludeBatches($torrents);
                $results[$ep] = array_merge($results[$ep], $torrents);
            }
        }

        // Deduplicate and sort per episode
        foreach ($results as $ep => $torrents) {
            $results[$ep] = $this->sortBySeeders($this->deduplicateByInfoHash($torrents));
        }

        return $results;
    }

    /**
     * Fetch and parse RSS feed from nyaa.si.
     */
    private function fetchRss(string $query): array
    {
        $response = Http::timeout(15)->get($this->baseUrl, [
            'page' => 'rss',
            'q' => $query,
            'c' => '1_2',
        ]);

        if ($response->failed()) {
            return [];
        }

        return $this->parseRssResponse($response->body());
    }

    /**
     * Parse nyaa.si RSS XML response into structured array.
     */
    private function parseRssResponse(string $xml): array
    {
        $xmlObj = @simplexml_load_string($xml);
        if ($xmlObj === false) {
            return [];
        }

        $torrents = [];
        foreach ($xmlObj->channel->item as $item) {
            $title = (string) $item->title;
            $link = (string) $item->link;
            $pubDate = (string) $item->pubDate;

            $ns = $item->getNamespaces(true);
            $nyaa = $item->children($ns['nyaa'] ?? 'https://nyaa.si/xmlns/nyaa');

            $infoHash = (string) ($nyaa->infoHash ?? '');

            $torrents[] = [
                'title' => $title,
                'link' => $link,
                'magnet' => $this->buildMagnetLink($infoHash, $title),
                'info_hash' => $infoHash,
                'seeders' => (int) ($nyaa->seeders ?? 0),
                'leechers' => (int) ($nyaa->leechers ?? 0),
                'downloads' => (int) ($nyaa->downloads ?? 0),
                'size' => (string) ($nyaa->size ?? ''),
                'category' => (string) ($nyaa->category ?? ''),
                'trusted' => (string) ($nyaa->trusted ?? '') === 'Yes',
                'remake' => (string) ($nyaa->remake ?? '') === 'Yes',
                'published_at' => $pubDate,
                'guid' => (string) $item->guid,
            ];
        }

        return $torrents;
    }

    /**
     * Build a magnet link from info hash and title.
     */
    public function buildMagnetLink(string $infoHash, string $title): string
    {
        $dn = rawurlencode($title);

        return "magnet:?xt=urn:btih:{$infoHash}&dn={$dn}";
    }

    /**
     * Filter torrents by default submitter name.
     */
    private function filterBySubmitter(array $torrents): array
    {
        if ($this->defaultSubmitter === '') {
            return $torrents;
        }

        return array_values(array_filter($torrents, fn ($t) => str_contains($t['title'], $this->defaultSubmitter)));
    }

    /**
     * Filter torrents by default quality.
     */
    private function filterByQuality(array $torrents): array
    {
        if ($this->defaultQuality === '') {
            return $torrents;
        }

        return array_values(array_filter($torrents, fn ($t) => str_contains($t['title'], $this->defaultQuality)));
    }

    /**
     * Exclude batch torrents (containing episode ranges like "01 ~ 12").
     */
    private function excludeBatches(array $torrents): array
    {
        return array_values(array_filter($torrents, fn ($t) => ! preg_match('/\d+\s*~\s*\d+/', $t['title'])));
    }

    /**
     * Sort torrents by seeders descending.
     */
    private function sortBySeeders(array $torrents): array
    {
        usort($torrents, fn ($a, $b) => $b['seeders'] <=> $a['seeders']);

        return $torrents;
    }

    /**
     * Remove duplicate torrents by info_hash.
     */
    private function deduplicateByInfoHash(array $torrents): array
    {
        $seen = [];
        $unique = [];

        foreach ($torrents as $torrent) {
            $hash = $torrent['info_hash'];
            if ($hash !== '' && ! isset($seen[$hash])) {
                $seen[$hash] = true;
                $unique[] = $torrent;
            } elseif ($hash === '') {
                $unique[] = $torrent;
            }
        }

        return $unique;
    }

    /**
     * Read from JSON cache.
     */
    private function getFromCache(string $key): ?array
    {
        $cachePath = config('media.nyaa.cache_path');
        $filePath = "{$cachePath}/{$key}.json";

        if (! file_exists($filePath)) {
            return null;
        }

        $mtime = filemtime($filePath);
        if ((time() - $mtime) > $this->cacheTtl) {
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
        $cachePath = config('media.nyaa.cache_path');

        if (! is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }

        $filePath = "{$cachePath}/{$key}.json";
        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
    }
}
