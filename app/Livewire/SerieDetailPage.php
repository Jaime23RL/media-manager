<?php

namespace App\Livewire;

use App\Services\CompareService;
use App\Services\NamingService;
use App\Services\ScannerService;
use App\Services\TmdbService;
use Livewire\Component;

class SerieDetailPage extends Component
{
    public $id;

    public $serie = null;

    public $tmdb = null;

    public $comparison = null;

    public $episodesBySeason = [];

    public $loading = false;

    public function mount($id): void
    {
        $this->id = $id;
        $this->loadSerie();
    }

    public function loadSerie(): void
    {
        $scanner = app(ScannerService::class);
        $data = $scanner->loadScan('animes');

        if (! $data || ! isset($data['series'][$this->id])) {
            return;
        }

        $this->serie = $data['series'][$this->id];
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

        $this->loading = false;
    }

    /**
     * Group TMDB episodes by season, marking each as have/missing.
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

            $status = 'missing';
            $filename = null;
            if (isset($haveMap[$season][$episode])) {
                $status = 'have';
                $filename = $haveMap[$season][$episode]['filename'];
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
