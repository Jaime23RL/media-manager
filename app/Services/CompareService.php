<?php

namespace App\Services;

class CompareService
{
    public function __construct(
        private NamingService $namingService
    ) {}

    /**
     * Compare local files against TMDB episodes for a single series.
     *
     * @param  array  $localFiles  List of filenames from local storage
     * @param  array  $tmdbEpisodes  List of episodes from TMDB [['season' => 1, 'episode' => 1, ...], ...]
     * @return array ['have' => [...], 'missing' => [...], 'unparseable' => [...]]
     */
    public function compare(array $localFiles, array $tmdbEpisodes): array
    {
        // Parse local files into normalized [season => [episode => filename]] map
        $localEpisodes = [];
        $unparseable = [];

        foreach ($localFiles as $file) {
            $parsed = $this->namingService->parse($file);
            if ($parsed) {
                $season = $parsed['season'];
                $episode = $parsed['episode'];
                $localEpisodes[$season][$episode] = $file;
            } else {
                $unparseable[] = $file;
            }
        }

        // Build TMDB lookup [season => [episode => episode_data]]
        $tmdbLookup = [];
        foreach ($tmdbEpisodes as $ep) {
            $tmdbLookup[$ep['season']][$ep['episode']] = $ep;
        }

        // Find what we have (local matches TMDB)
        $have = [];
        foreach ($localEpisodes as $season => $episodes) {
            foreach ($episodes as $episode => $filename) {
                if (isset($tmdbLookup[$season][$episode])) {
                    $have[] = [
                        'season' => $season,
                        'episode' => $episode,
                        'filename' => $filename,
                        'tmdb_name' => $tmdbLookup[$season][$episode]['name'] ?? '',
                    ];
                }
            }
        }

        // Find what's missing (TMDB has it, local doesn't)
        $missing = [];
        foreach ($tmdbLookup as $season => $episodes) {
            foreach ($episodes as $episode => $epData) {
                if (! isset($localEpisodes[$season][$episode])) {
                    $missing[] = [
                        'season' => $season,
                        'episode' => $episode,
                        'name' => $epData['name'] ?? '',
                        'air_date' => $epData['air_date'] ?? null,
                    ];
                }
            }
        }

        // Sort results
        usort($have, fn ($a, $b) => $a['season'] <=> $b['season'] ?: $a['episode'] <=> $b['episode']);
        usort($missing, fn ($a, $b) => $a['season'] <=> $b['season'] ?: $a['episode'] <=> $b['episode']);

        return [
            'have' => $have,
            'missing' => $missing,
            'unparseable' => $unparseable,
        ];
    }

    /**
     * Compare multiple series at once.
     *
     * @param  array  $scanData  Scanner output [['name' => '...', 'files' => [...], ...], ...]
     * @param  array  $tmdbResults  TmdbService lookup results ['name' => ['tmdb_id' => ..., ...], ...]
     * @return array Keyed by series name => ['local' => ..., 'tmdb' => ..., 'comparison' => ...]
     */
    public function compareMultiple(array $scanData, array $tmdbResults): array
    {
        $results = [];

        foreach ($scanData as $serie) {
            $name = $serie['name'];
            $tmdbInfo = $tmdbResults[$name] ?? null;

            if (! $tmdbInfo) {
                $results[$name] = [
                    'local' => $serie,
                    'tmdb' => null,
                    'comparison' => null,
                ];

                continue;
            }

            $tmdbEpisodes = app(TmdbService::class)->getAllEpisodes($tmdbInfo['tmdb_id']);
            $comparison = $this->compare($serie['files'], $tmdbEpisodes);

            $results[$name] = [
                'local' => $serie,
                'tmdb' => $tmdbInfo,
                'comparison' => $comparison,
            ];
        }

        return $results;
    }
}
