<?php

namespace Tests\Feature;

use App\Livewire\SerieDetailPage;
use App\Services\QbittorrentService;
use App\Services\RenamerService;
use App\Services\TmdbService;
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

    public function test_add_to_qbittorrent_sends_magnet_with_save_path(): void
    {
        File::makeDirectory($this->tempDir.'/Test Anime', 0755, true);
        File::makeDirectory($this->tempDir.'/Test Anime/Season 1', 0755, true);

        $magnet = 'magnet:?xt=urn:btih:abc123&dn=Test';

        $service = $this->createMock(QbittorrentService::class);
        $service->expects($this->once())
            ->method('addMagnet')
            ->with($magnet, $this->tempDir.'/Test Anime/Season 1')
            ->willReturn(true);

        app()->instance(QbittorrentService::class, $service);

        $component = Livewire::test(SerieDetailPage::class, ['id' => 0]);

        $component->set('serie', [
            'name' => 'Test Anime',
            'path' => $this->tempDir.'/Test Anime',
            'files' => ['S01E01.mkv'],
            'type' => 'animes',
            'file_count' => 1,
        ]);
        $component->set('existingSeasonFolders', [1]);

        $component->call('addToQbittorrent', $magnet, 1);

        $component->assertSet('qbMessage', 'Torrent added to download queue');
        $component->assertSet('qbMessageType', 'success');
    }

    public function test_add_to_qbittorrent_blocked_when_no_season_folder(): void
    {
        File::makeDirectory($this->tempDir.'/Test Anime', 0755, true);
        // No Season 1 folder

        $service = $this->createMock(QbittorrentService::class);
        $service->expects($this->never())->method('addMagnet');

        app()->instance(QbittorrentService::class, $service);

        $component = Livewire::test(SerieDetailPage::class, ['id' => 0]);

        $component->set('serie', [
            'name' => 'Test Anime',
            'path' => $this->tempDir.'/Test Anime',
            'files' => ['S01E01.mkv'],
            'type' => 'animes',
            'file_count' => 1,
        ]);
        $component->set('existingSeasonFolders', []);

        $component->call('addToQbittorrent', 'magnet:?xt=urn:btih:abc123', 1);

        $component->assertSet('qbMessage', 'Create Season 1 folder first');
        $component->assertSet('qbMessageType', 'error');
    }

    public function test_add_to_qbittorrent_shows_error_on_failure(): void
    {
        File::makeDirectory($this->tempDir.'/Test Anime/Season 1', 0755, true);

        $service = $this->createMock(QbittorrentService::class);
        $service->expects($this->once())
            ->method('addMagnet')
            ->willReturn(false);

        app()->instance(QbittorrentService::class, $service);

        $component = Livewire::test(SerieDetailPage::class, ['id' => 0]);

        $component->set('serie', [
            'name' => 'Test Anime',
            'path' => $this->tempDir.'/Test Anime',
            'files' => ['S01E01.mkv'],
            'type' => 'animes',
            'file_count' => 1,
        ]);
        $component->set('existingSeasonFolders', [1]);

        $component->call('addToQbittorrent', 'magnet:?xt=urn:btih:abc123', 1);

        $component->assertSet('qbMessage', 'Failed to add torrent. Check qBittorrent connection.');
        $component->assertSet('qbMessageType', 'error');
    }

    public function test_add_to_qbittorrent_without_serie_path(): void
    {
        $component = Livewire::test(SerieDetailPage::class, ['id' => 0]);

        $component->set('serie', [
            'name' => 'Test Anime',
            'path' => null,
            'files' => ['S01E01.mkv'],
            'type' => 'animes',
            'file_count' => 1,
        ]);

        $component->call('addToQbittorrent', 'magnet:?xt=urn:btih:abc123', 1);

        $component->assertSet('qbMessage', 'Series path not available');
        $component->assertSet('qbMessageType', 'error');
    }

    public function test_rename_series_shows_success(): void
    {
        $mock = $this->createMock(RenamerService::class);
        $mock->expects($this->once())
            ->method('rename')
            ->with($this->tempDir.'/Test Anime')
            ->willReturn([
                'success' => true,
                'renamed' => 3,
                'already_correct' => 0,
                'warnings_count' => 0,
                'output' => '',
            ]);

        app()->instance(RenamerService::class, $mock);

        $component = Livewire::test(SerieDetailPage::class, ['id' => 0]);

        $component->set('serie', [
            'name' => 'Test Anime',
            'path' => $this->tempDir.'/Test Anime',
            'files' => ['S01E01.mkv'],
            'type' => 'animes',
            'file_count' => 1,
        ]);

        $component->call('renameSeries');

        $component->assertSet('renameMessage', 'Renamed 3 file(s)');
        $component->assertSet('renameMessageType', 'success');
    }

    public function test_rename_series_shows_already_correct(): void
    {
        $mock = $this->createMock(RenamerService::class);
        $mock->expects($this->once())
            ->method('rename')
            ->willReturn([
                'success' => true,
                'renamed' => 0,
                'already_correct' => 5,
                'warnings_count' => 0,
                'output' => '',
            ]);

        app()->instance(RenamerService::class, $mock);

        $component = Livewire::test(SerieDetailPage::class, ['id' => 0]);

        $component->set('serie', [
            'name' => 'Test Anime',
            'path' => $this->tempDir.'/Test Anime',
            'files' => ['S01E01.mkv'],
            'type' => 'animes',
            'file_count' => 1,
        ]);

        $component->call('renameSeries');

        $component->assertSet('renameMessage', '5 file(s) already correctly named');
        $component->assertSet('renameMessageType', 'info');
    }

    public function test_rename_series_shows_error_on_failure(): void
    {
        $mock = $this->createMock(RenamerService::class);
        $mock->expects($this->once())
            ->method('rename')
            ->willReturn([
                'success' => false,
                'renamed' => 0,
                'already_correct' => 0,
                'warnings_count' => 0,
                'output' => '',
            ]);

        app()->instance(RenamerService::class, $mock);

        $component = Livewire::test(SerieDetailPage::class, ['id' => 0]);

        $component->set('serie', [
            'name' => 'Test Anime',
            'path' => $this->tempDir.'/Test Anime',
            'files' => ['S01E01.mkv'],
            'type' => 'animes',
            'file_count' => 1,
        ]);

        $component->call('renameSeries');

        $component->assertSet('renameMessage', 'Renaming failed. Is Python installed?');
        $component->assertSet('renameMessageType', 'error');
    }

    public function test_cache_auto_refreshes_when_new_files_detected(): void
    {
        $scanPath = storage_path('app/cache/local/animes.json');
        $backupPath = $scanPath.'.backup';

        // Backup original scan cache
        if (file_exists($scanPath)) {
            copy($scanPath, $backupPath);
        }

        // Step 1: Write initial scan cache with one file to the real storage path
        if (! File::isDirectory(dirname($scanPath))) {
            File::makeDirectory(dirname($scanPath), 0755, true);
        }
        $scanData = [
            'scanned_at' => now()->toISOString(),
            'path' => $this->tempDir,
            'series' => [
                [
                    'name' => 'Test Anime',
                    'path' => $this->tempDir.'/Test Anime',
                    'files' => ['S01E01.mkv'],
                    'type' => 'animes',
                    'file_count' => 1,
                ],
            ],
        ];
        file_put_contents($scanPath, json_encode($scanData));

        File::makeDirectory($this->tempDir.'/Test Anime', 0755, true);

        // Step 2: Create a stale TMDB cache (hash matches old file list)
        $files = ['S01E01.mkv'];
        sort($files);
        $cacheData = [
            'tmdb' => [
                'tmdb_id' => 12345,
                'name' => 'Test Anime',
                'overview' => '',
                'poster_path' => null,
                'first_air_date' => '2020-01-01',
            ],
            'comparison' => [
                'have' => [
                    ['season' => 1, 'episode' => 1, 'filename' => 'S01E01.mkv', 'tmdb_name' => 'Pilot'],
                ],
                'missing' => [
                    ['season' => 1, 'episode' => 2, 'name' => 'Second', 'air_date' => '2024-01-08'],
                ],
                'upcoming' => [],
                'unparseable' => [],
            ],
            'episodes_by_season' => [
                1 => [
                    ['episode' => 1, 'name' => 'Pilot', 'air_date' => '2024-01-01', 'status' => 'have', 'filename' => 'S01E01.mkv'],
                    ['episode' => 2, 'name' => 'Second', 'air_date' => '2024-01-08', 'status' => 'missing', 'filename' => null],
                ],
            ],
            'custom_names' => [],
            'cached_at' => now()->subHour()->toISOString(),
            'files_hash' => md5(serialize($files)),
        ];
        file_put_contents($this->cacheDir.'/serie_'.md5('Test Anime').'.json', json_encode($cacheData));

        // Step 3: Update scan cache with a new file (overwrite the real path)
        $scanData['series'][0]['files'] = ['S01E01.mkv', 'S01E02.mkv'];
        $scanData['series'][0]['file_count'] = 2;
        file_put_contents($scanPath, json_encode($scanData));

        // Step 4: Mock TMDB so refreshComparison() doesn't hit the API
        $mockTmdb = $this->createMock(TmdbService::class);
        $mockTmdb->method('getAllEpisodes')->willReturn([
            ['season' => 1, 'episode' => 1, 'name' => 'Pilot', 'air_date' => '2024-01-01'],
            ['season' => 1, 'episode' => 2, 'name' => 'Second', 'air_date' => '2024-01-08'],
        ]);
        app()->instance(TmdbService::class, $mockTmdb);

        // Step 5: Mount component — loadSerie() gets new files, loadFromCache() detects stale hash
        $component = Livewire::test(SerieDetailPage::class, ['id' => 0]);

        // Assert the comparison was auto-refreshed and S01E02 is now have
        $component->assertSet('episodesBySeason.1', function ($season) {
            $ep1 = collect($season)->firstWhere('episode', 1);
            $ep2 = collect($season)->firstWhere('episode', 2);

            return $ep1['status'] === 'have' && $ep2['status'] === 'have';
        });

        // Restore original scan cache
        if (file_exists($backupPath)) {
            copy($backupPath, $scanPath);
            unlink($backupPath);
        } elseif (file_exists($scanPath)) {
            unlink($scanPath);
        }
    }
}
