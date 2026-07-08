<?php

namespace App\Livewire;

use App\Services\JikanService;
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

    public array $nameSuggestions = [];

    public string $selectedNameOption = '';

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

        // Build name suggestions from main name + alternative titles + Jikan romaji
        $this->nameSuggestions = [];
        $seen = [];

        // English name (from TMDB)
        $englishName = $this->selectedSerie['name'];
        if ($englishName !== '') {
            $this->nameSuggestions[] = [
                'name' => $englishName,
                'label' => 'English',
            ];
            $seen[strtolower($englishName)] = true;
        }

        // Search Jikan for romaji
        $jikan = app(JikanService::class);
        $jikanResult = $jikan->searchByName($this->selectedSerie['name']);

        if ($jikanResult) {
            $romaji = $jikanResult['title'] ?? '';
            if ($romaji !== '' && ! isset($seen[strtolower($romaji)])) {
                $this->nameSuggestions[] = [
                    'name' => $romaji,
                    'label' => 'Romaji',
                ];
                $seen[strtolower($romaji)] = true;
            }

            // Also check Jikan's English title if different
            $jikanEnglish = $jikanResult['title_english'] ?? '';
            if ($jikanEnglish !== '' && ! isset($seen[strtolower($jikanEnglish)])) {
                $this->nameSuggestions[] = [
                    'name' => $jikanEnglish,
                    'label' => 'English (MAL)',
                ];
                $seen[strtolower($jikanEnglish)] = true;
            }
        }

        // Original name (Japanese characters from TMDB)
        $originalName = $this->selectedSerie['original_name'];
        if ($originalName !== '' && ! isset($seen[strtolower($originalName)])) {
            $this->nameSuggestions[] = [
                'name' => $originalName,
                'label' => 'Japanese',
            ];
            $seen[strtolower($originalName)] = true;
        }

        // Alternative titles from TMDB
        $altTitles = $tmdb->getAlternativeTitles($tmdbId);
        foreach ($altTitles as $alt) {
            $title = $alt['title'] ?? '';
            $lang = $alt['language'] ?? '';
            $iso = $alt['iso_3166_1'] ?? '';

            if ($title === '' || isset($seen[strtolower($title)])) {
                continue;
            }

            $seen[strtolower($title)] = true;

            $label = match (true) {
                $lang === 'ja' => 'Japanese',
                $iso === 'JP' => 'Japanese',
                $lang === 'en' => 'English',
                $iso === 'US' || $iso === 'GB' => 'English',
                default => strtoupper($iso ?: $lang),
            };

            $this->nameSuggestions[] = [
                'name' => $title,
                'label' => $label,
            ];
        }

        // Default to English name
        $this->folderName = $englishName;
        $this->selectedNameOption = $englishName;

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

    /**
     * Handle name option selection.
     */
    public function selectNameOption(string $name): void
    {
        $this->selectedNameOption = $name;
        $this->folderName = $name;
    }

    /**
     * Update folder name when selected name option changes.
     */
    public function updatedSelectedNameOption(string $value): void
    {
        if ($value !== 'custom') {
            $this->folderName = $value;
        }
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
        $this->nameSuggestions = [];
        $this->selectedNameOption = '';
        $this->seasons = [];
        $this->selectedSeasons = [];
    }

    public function resetSearch(): void
    {
        $this->searchQuery = '';
        $this->results = [];
        $this->selectedSerie = null;
        $this->folderName = '';
        $this->nameSuggestions = [];
        $this->selectedNameOption = '';
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
