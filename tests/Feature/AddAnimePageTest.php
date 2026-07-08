<?php

namespace Tests\Feature;

use App\Livewire\AddAnimePage;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

class AddAnimePageTest extends TestCase
{
    public function test_search_requires_query(): void
    {
        $component = Livewire::test(AddAnimePage::class);

        $component->set('searchQuery', '')->call('search');

        $component->assertSet('results', []);
    }

    public function test_creates_folder_structure(): void
    {
        $tempDir = sys_get_temp_dir().'/add_anime_test_'.uniqid();
        File::makeDirectory($tempDir, 0755, true);

        config(['media.paths.animes' => $tempDir]);

        $component = Livewire::test(AddAnimePage::class);

        $component->set('selectedSerie', [
            'tmdb_id' => 12345,
            'name' => 'Test Anime',
            'original_name' => 'Test Anime',
            'overview' => 'A test anime.',
            'poster_path' => null,
            'first_air_date' => '2020-01-01',
        ])
            ->set('folderName', 'Test Anime')
            ->set('seasons', [
                ['number' => 1, 'name' => 'Season 1', 'episode_count' => 12, 'air_date' => '2020-01-01', 'is_aired' => true],
                ['number' => 2, 'name' => 'Season 2', 'episode_count' => 12, 'air_date' => '2020-07-01', 'is_aired' => true],
            ])
            ->set('selectedSeasons', [1, 2])
            ->call('createFolders');

        $component->assertSet('created', true);
        $this->assertDirectoryExists($tempDir.'/Test Anime');
        $this->assertDirectoryExists($tempDir.'/Test Anime/Season 1');
        $this->assertDirectoryExists($tempDir.'/Test Anime/Season 2');

        File::deleteDirectory($tempDir);
    }

    public function test_back_to_results_resets_selection(): void
    {
        $component = Livewire::test(AddAnimePage::class);

        $component->set('selectedSerie', [
            'tmdb_id' => 1,
            'name' => 'Test',
            'poster_path' => null,
        ])
            ->set('folderName', 'Test')
            ->set('nameSuggestions', [['name' => 'Test', 'label' => 'English']])
            ->call('backToResults');

        $component->assertSet('selectedSerie', null);
        $component->assertSet('folderName', '');
        $component->assertSet('nameSuggestions', []);
        $component->assertSet('seasons', []);
    }

    public function test_reset_search_clears_everything(): void
    {
        $component = Livewire::test(AddAnimePage::class);

        $component->set('searchQuery', 'Code Geass')
            ->set('results', [['id' => 1, 'name' => 'Code Geass', 'poster_path' => null]])
            ->set('selectedSerie', ['tmdb_id' => 1, 'name' => 'Code Geass', 'poster_path' => null])
            ->set('nameSuggestions', [['name' => 'Code Geass', 'label' => 'English']])
            ->set('created', true)
            ->call('resetSearch');

        $component->assertSet('searchQuery', '');
        $component->assertSet('results', []);
        $component->assertSet('selectedSerie', null);
        $component->assertSet('created', false);
    }
}
