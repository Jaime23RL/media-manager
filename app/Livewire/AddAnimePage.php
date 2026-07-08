<?php

namespace App\Livewire;

use App\Services\ScannerService;
use App\Services\TmdbService;
use Illuminate\Support\Facades\File;
use Livewire\Component;

class AddAnimePage extends Component
{
    public string $searchQuery = '';

    public array $results = [];

    public ?array $selectedSerie = null;

    public string $folderName = '';

    public array $seasons = [];

    public array $selectedSeasons = [];

    public bool $loading = false;

    public bool $created = false;

    public string $createdPath = '';

    public string $searchError = '';

    public function search(): void
    {
        $query = trim($this->searchQuery);

        if ($query === '') {
            return;
        }

        $this->loading = true;
        $this->results = [];
        $this->selectedSerie = null;
        $this->searchError = '';

        $tmdb = app(TmdbService::class);
        $this->results = $tmdb->searchTvAll($query);

        if (empty($this->results)) {
            $this->searchError = 'No results found for "'.$query.'"';
        }

        $this->loading = false;
    }

    public function select(int $tmdbId): void
    {
        $this->loading = true;

        $tmdb = app(TmdbService::class);
        $details = $tmdb->getTvDetails($tmdbId);

        if (! $details) {
            $this->loading = false;

            return;
        }

        $this->selectedSerie = [
            'tmdb_id' => $details['id'],
            'name' => $details['name'] ?? $details['original_name'] ?? '',
            'original_name' => $details['original_name'] ?? '',
            'overview' => $details['overview'] ?? '',
            'poster_path' => $details['poster_path'] ?? null,
            'first_air_date' => $details['first_air_date'] ?? null,
        ];

        $this->folderName = $this->selectedSerie['name'];

        // Process seasons
        $this->seasons = [];
        $this->selectedSeasons = [];
        $today = date('Y-m-d');

        foreach ($details['seasons'] ?? [] as $season) {
            $seasonNumber = $season['season_number'];

            if ($seasonNumber === 0) {
                continue; // Skip specials
            }

            $airDate = $season['air_date'] ?? null;
            $isAired = $airDate !== null && $airDate !== '' && $airDate <= $today;

            $this->seasons[] = [
                'number' => $seasonNumber,
                'name' => $season['name'] ?? "Season {$seasonNumber}",
                'episode_count' => $season['episode_count'] ?? 0,
                'air_date' => $airDate,
                'is_aired' => $isAired,
            ];

            if ($isAired) {
                $this->selectedSeasons[] = $seasonNumber;
            }
        }

        usort($this->seasons, fn ($a, $b) => $a['number'] <=> $b['number']);

        $this->loading = false;
    }

    public function createFolders(): void
    {
        if (! $this->selectedSerie || $this->folderName === '') {
            return;
        }

        $basePath = config('media.paths.animes');

        if (! File::isDirectory($basePath)) {
            return;
        }

        $seriesPath = $basePath.'/'.$this->folderName;

        // Create series folder
        if (! File::isDirectory($seriesPath)) {
            File::makeDirectory($seriesPath, 0755, true);
        }

        // Create selected season folders
        $scanner = app(ScannerService::class);
        $createdSeasons = [];

        foreach ($this->selectedSeasons as $seasonNumber) {
            if ($scanner->createSeasonFolder($seriesPath, $seasonNumber)) {
                $createdSeasons[] = $seasonNumber;
            }
        }

        $this->created = true;
        $this->createdPath = $seriesPath;
    }

    public function backToResults(): void
    {
        $this->selectedSerie = null;
        $this->folderName = '';
        $this->seasons = [];
        $this->selectedSeasons = [];
    }

    public function resetSearch(): void
    {
        $this->searchQuery = '';
        $this->results = [];
        $this->selectedSerie = null;
        $this->folderName = '';
        $this->seasons = [];
        $this->selectedSeasons = [];
        $this->created = false;
        $this->createdPath = '';
        $this->searchError = '';
    }

    public function render()
    {
        return view('livewire.add-anime-page');
    }
}
