<?php

namespace Tests\Feature;

use App\Livewire\SerieDetailPage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class SerieDetailPageTest extends TestCase
{
    private string $tempDir;

    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir().'/serie_detail_test_'.uniqid();
        $this->cacheDir = sys_get_temp_dir().'/serie_cache_test_'.uniqid();

        File::makeDirectory($this->tempDir, 0755, true);
        File::makeDirectory($this->cacheDir, 0755, true);

        config([
            'media.paths.animes' => $this->tempDir,
            'media.cache_path' => $this->cacheDir,
            'media.nyaa.cache_path' => sys_get_temp_dir().'/nyaa_test_'.uniqid(),
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tempDir);
        File::deleteDirectory($this->cacheDir);

        parent::tearDown();
    }

    private function createScanCache(array $series): void
    {
        $data = [
            'scanned_at' => now()->toISOString(),
            'path' => $this->tempDir,
            'series' => $series,
        ];

        file_put_contents($this->cacheDir.'/animes.json', json_encode($data, JSON_PRETTY_PRINT));
    }

    public function test_search_nyaa_for_episode_sets_results(): void
    {
        $this->createScanCache([
            [
                'name' => 'Test Anime',
                'path' => $this->tempDir.'/Test Anime',
                'files' => ['S01E01.mkv'],
                'type' => 'animes',
                'file_count' => 1,
            ],
        ]);

        File::makeDirectory($this->tempDir.'/Test Anime', 0755, true);

        $nyaaRss = $this->getSampleNyaaRss();

        Http::fake([
            '*' => Http::response($nyaaRss, 200),
        ]);

        $component = Livewire::test(SerieDetailPage::class, ['id' => 0]);

        $component->set('tmdb', [
            'tmdb_id' => 12345,
            'name' => 'Test Anime',
            'poster_path' => null,
            'overview' => '',
            'first_air_date' => '2020-01-01',
        ])
            ->set('episodesBySeason', [
                1 => [
                    ['episode' => 1, 'name' => 'Episode 1', 'status' => 'missing', 'air_date' => '2024-01-01', 'filename' => null],
                ],
            ])
            ->call('searchNyaaForEpisode', 1, 1);

        $component->assertSet('nyaaResults.1_1', function ($results) {
            return count($results) > 0 && str_contains($results[0]['title'], 'Erai-raws');
        });
    }

    public function test_search_nyaa_for_season_searches_missing(): void
    {
        $this->createScanCache([
            [
                'name' => 'Test Anime',
                'path' => $this->tempDir.'/Test Anime',
                'files' => ['S01E01.mkv'],
                'type' => 'animes',
                'file_count' => 1,
            ],
        ]);

        File::makeDirectory($this->tempDir.'/Test Anime', 0755, true);

        $nyaaRss = $this->getSampleNyaaRss();

        Http::fake([
            '*' => Http::response($nyaaRss, 200),
        ]);

        $component = Livewire::test(SerieDetailPage::class, ['id' => 0]);

        $component->set('tmdb', [
            'tmdb_id' => 12345,
            'name' => 'Test Anime',
            'poster_path' => null,
            'overview' => '',
            'first_air_date' => '2020-01-01',
        ])
            ->set('episodesBySeason', [
                1 => [
                    ['episode' => 1, 'name' => 'Episode 1', 'status' => 'have', 'air_date' => '2024-01-01', 'filename' => 'S01E01.mkv'],
                    ['episode' => 2, 'name' => 'Episode 2', 'status' => 'missing', 'air_date' => '2024-01-08', 'filename' => null],
                    ['episode' => 3, 'name' => 'Episode 3', 'status' => 'missing', 'air_date' => '2024-01-15', 'filename' => null],
                ],
            ])
            ->call('searchNyaaForSeason', 1);

        $component->assertSet('searchingNyaa', false);
        $component->assertSet('nyaaResults.1_2', function ($results) {
            return is_array($results);
        });
        $component->assertSet('nyaaResults.1_3', function ($results) {
            return is_array($results);
        });
    }

    private function getSampleNyaaRss(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:atom="http://www.w3.org/2005/Atom" xmlns:nyaa="https://nyaa.si/xmlns/nyaa" version="2.0">
    <channel>
        <title>Nyaa - Test</title>
        <item>
            <title>[Erai-raws] Test Anime - 01 [1080p CR WEB-DL HEVC AAC][MultiSub]</title>
            <link>https://nyaa.si/download/123456.torrent</link>
            <guid isPermaLink="true">https://nyaa.si/view/123456</guid>
            <pubDate>Sat, 04 Jul 2026 16:33:03 -0000</pubDate>
            <nyaa:seeders>375</nyaa:seeders>
            <nyaa:leechers>2</nyaa:leechers>
            <nyaa:downloads>5722</nyaa:downloads>
            <nyaa:infoHash>fb52f88f106ccb015aa327d0e2bf0cba6f17ac81</nyaa:infoHash>
            <nyaa:categoryId>1_2</nyaa:categoryId>
            <nyaa:category>Anime - English-translated</nyaa:category>
            <nyaa:size>551.4 MiB</nyaa:size>
            <nyaa:trusted>Yes</nyaa:trusted>
            <nyaa:remake>No</nyaa:remake>
        </item>
    </channel>
</rss>
XML;
    }

    public function test_custom_name_saved_after_successful_search(): void
    {
        $this->createScanCache([
            [
                'name' => 'Test Anime',
                'path' => $this->tempDir.'/Test Anime',
                'files' => ['S01E01.mkv'],
                'type' => 'animes',
                'file_count' => 1,
            ],
        ]);

        File::makeDirectory($this->tempDir.'/Test Anime', 0755, true);

        $nyaaRss = $this->getSampleNyaaRss();

        Http::fake([
            '*' => Http::response($nyaaRss, 200),
        ]);

        $component = Livewire::test(SerieDetailPage::class, ['id' => 0]);

        $component->set('tmdb', [
            'tmdb_id' => 12345,
            'name' => 'Test Anime',
            'poster_path' => null,
            'overview' => '',
            'first_air_date' => '2020-01-01',
        ])
            ->set('episodesBySeason', [
                1 => [
                    ['episode' => 1, 'name' => 'Episode 1', 'status' => 'missing', 'air_date' => '2024-01-01', 'filename' => null],
                ],
            ])
            ->set('nyaaCustomSeason', 1)
            ->set('nyaaCustomEpisode', 1)
            ->set('nyaaCustomQuery', 'Test Anime Custom')
            ->call('searchNyaaCustom');

        $component->assertSet('customNames', [1 => 'Test Anime Custom']);
        $component->assertSet('nyaaResults.1_1', function ($results) {
            return count($results) > 0;
        });
    }

    public function test_custom_name_used_in_subsequent_search(): void
    {
        $this->createScanCache([
            [
                'name' => 'Test Anime',
                'path' => $this->tempDir.'/Test Anime',
                'files' => ['S01E01.mkv'],
                'type' => 'animes',
                'file_count' => 1,
            ],
        ]);

        File::makeDirectory($this->tempDir.'/Test Anime', 0755, true);

        $nyaaRss = $this->getSampleNyaaRss();

        Http::fake([
            '*' => Http::response($nyaaRss, 200),
        ]);

        $component = Livewire::test(SerieDetailPage::class, ['id' => 0]);

        $component->set('tmdb', [
            'tmdb_id' => 12345,
            'name' => 'Test Anime',
            'poster_path' => null,
            'overview' => '',
            'first_air_date' => '2020-01-01',
        ])
            ->set('episodesBySeason', [
                1 => [
                    ['episode' => 1, 'name' => 'Episode 1', 'status' => 'missing', 'air_date' => '2024-01-01', 'filename' => null],
                    ['episode' => 2, 'name' => 'Episode 2', 'status' => 'missing', 'air_date' => '2024-01-08', 'filename' => null],
                ],
            ])
            ->set('customNames', [1 => 'Test Anime Custom'])
            ->call('searchNyaaForEpisode', 1, 2);

        $component->assertSet('nyaaResults.1_2', function ($results) {
            return count($results) > 0;
        });

        // Verify the search used the custom name by checking the debug log
        $component->assertSet('nyaaDebugLog', function ($log) {
            $startEntry = collect($log)->firstWhere('step', 'search_episode_start');

            return $startEntry
                && in_array('Test Anime Custom - 02', $startEntry['names'])
                && $startEntry['custom_name'] === 'Test Anime Custom'
                && $startEntry['custom_name_with_ep'] === 'Test Anime Custom - 02';
        });
    }

    public function test_reset_custom_name(): void
    {
        $this->createScanCache([
            [
                'name' => 'Test Anime',
                'path' => $this->tempDir.'/Test Anime',
                'files' => ['S01E01.mkv'],
                'type' => 'animes',
                'file_count' => 1,
            ],
        ]);

        File::makeDirectory($this->tempDir.'/Test Anime', 0755, true);

        $component = Livewire::test(SerieDetailPage::class, ['id' => 0]);

        $component->set('customNames', [1 => 'Test Anime Custom'])
            ->call('resetCustomName', 1);

        $component->assertSet('customNames', []);
    }

    public function test_season_search_sets_custom_input_when_no_results(): void
    {
        $this->createScanCache([
            [
                'name' => 'Test Anime',
                'path' => $this->tempDir.'/Test Anime',
                'files' => ['S01E01.mkv'],
                'type' => 'animes',
                'file_count' => 1,
            ],
        ]);

        File::makeDirectory($this->tempDir.'/Test Anime', 0755, true);

        $emptyRss = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:atom="http://www.w3.org/2005/Atom" xmlns:nyaa="https://nyaa.si/xmlns/nyaa" version="2.0">
    <channel>
        <title>Nyaa - Test</title>
    </channel>
</rss>
XML;

        Http::fake([
            '*' => Http::response($emptyRss, 200),
        ]);

        $component = Livewire::test(SerieDetailPage::class, ['id' => 0]);

        $component->set('tmdb', [
            'tmdb_id' => 12345,
            'name' => 'Test Anime',
            'poster_path' => null,
            'overview' => '',
            'first_air_date' => '2020-01-01',
        ])
            ->set('episodesBySeason', [
                1 => [
                    ['episode' => 1, 'name' => 'Episode 1', 'status' => 'missing', 'air_date' => '2024-01-01', 'filename' => null],
                ],
            ])
            ->call('searchNyaaForSeason', 1);

        $component->assertSet('nyaaCustomSeason', 1);
        $component->assertSet('nyaaCustomEpisode', 0);
        $component->assertSet('nyaaSearchMessage', fn ($msg) => str_contains($msg, 'No results found'));
    }

    public function test_custom_name_saved_for_season_search(): void
    {
        $this->createScanCache([
            [
                'name' => 'Test Anime',
                'path' => $this->tempDir.'/Test Anime',
                'files' => ['S01E01.mkv'],
                'type' => 'animes',
                'file_count' => 1,
            ],
        ]);

        File::makeDirectory($this->tempDir.'/Test Anime', 0755, true);

        $nyaaRss = $this->getSampleNyaaRss();

        Http::fake([
            '*' => Http::response($nyaaRss, 200),
        ]);

        $component = Livewire::test(SerieDetailPage::class, ['id' => 0]);

        $component->set('tmdb', [
            'tmdb_id' => 12345,
            'name' => 'Test Anime',
            'poster_path' => null,
            'overview' => '',
            'first_air_date' => '2020-01-01',
        ])
            ->set('episodesBySeason', [
                1 => [
                    ['episode' => 1, 'name' => 'Episode 1', 'status' => 'missing', 'air_date' => '2024-01-01', 'filename' => null],
                ],
            ])
            ->set('nyaaCustomSeason', 1)
            ->set('nyaaCustomEpisode', 0)
            ->set('nyaaCustomQuery', 'Test Anime Custom')
            ->call('searchNyaaCustom');

        $component->assertSet('customNames', [1 => 'Test Anime Custom']);
    }
}
