<?php

namespace App\Livewire;

use App\Services\NamingService;
use App\Services\ScannerService;
use App\Services\TmdbService;
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
        $names = array_column($this->series, 'name');
        $tmdbResults = $tmdb->lookupMultiple($names);

        // Enrich series data with TMDB info
        foreach ($this->series as &$serie) {
            $tmdbInfo = $tmdbResults[$serie['name']] ?? null;
            $serie['tmdb'] = $tmdbInfo;

            if ($tmdbInfo) {
                $episodes = $tmdb->getAllEpisodes($tmdbInfo['tmdb_id']);
                $missing = 0;
                $have = 0;

                // Quick count without full comparison
                $parsedEpisodes = [];
                foreach ($serie['files'] as $file) {
                    $parsed = app(NamingService::class)->parse($file);
                    if ($parsed) {
                        $parsedEpisodes[$parsed['season']][$parsed['episode']] = true;
                    }
                }

                foreach ($episodes as $ep) {
                    if (isset($parsedEpisodes[$ep['season']][$ep['episode']])) {
                        $have++;
                    } else {
                        $missing++;
                    }
                }

                $serie['total_episodes'] = count($episodes);
                $serie['have_count'] = $have;
                $serie['missing_count'] = $missing;
            }
        }
        unset($serie);

        $this->searching = false;
    }

    public function render()
    {
        return view('livewire.series-page');
    }
}
