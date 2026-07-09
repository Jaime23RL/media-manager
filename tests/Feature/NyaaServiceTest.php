<?php

namespace Tests\Feature;

use App\Services\NyaaService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NyaaServiceTest extends TestCase
{
    private string $sampleRss = <<<'XML'
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
        <item>
            <title>[Other Group] Test Anime - 01 [720p]</title>
            <link>https://nyaa.si/download/123457.torrent</link>
            <guid isPermaLink="true">https://nyaa.si/view/123457</guid>
            <pubDate>Sat, 04 Jul 2026 15:28:02 -0000</pubDate>
            <nyaa:seeders>80</nyaa:seeders>
            <nyaa:leechers>2</nyaa:leechers>
            <nyaa:downloads>1554</nyaa:downloads>
            <nyaa:infoHash>85f7c42758a2f0e90f478c1a7b4b7f55a7e8e3eb</nyaa:infoHash>
            <nyaa:categoryId>1_2</nyaa:categoryId>
            <nyaa:category>Anime - English-translated</nyaa:category>
            <nyaa:size>629.2 MiB</nyaa:size>
            <nyaa:trusted>No</nyaa:trusted>
            <nyaa:remake>No</nyaa:remake>
        </item>
        <item>
            <title>[Erai-raws] Test Anime - 01 ~ 12 [1080p][Multiple Subtitle]</title>
            <link>https://nyaa.si/download/123458.torrent</link>
            <guid isPermaLink="true">https://nyaa.si/view/123458</guid>
            <pubDate>Sun, 05 Jun 2022 20:34:45 -0000</pubDate>
            <nyaa:seeders>42</nyaa:seeders>
            <nyaa:leechers>1</nyaa:leechers>
            <nyaa:downloads>4455</nyaa:downloads>
            <nyaa:infoHash>03df500192a339adbddf6775e82bc50209e311ba</nyaa:infoHash>
            <nyaa:categoryId>1_2</nyaa:categoryId>
            <nyaa:category>Anime - English-translated</nyaa:category>
            <nyaa:size>16.2 GiB</nyaa:size>
            <nyaa:trusted>Yes</nyaa:trusted>
            <nyaa:remake>No</nyaa:remake>
        </item>
    </channel>
</rss>
XML;

    protected function setUp(): void
    {
        parent::setUp();

        config(['media.nyaa.cache_path' => sys_get_temp_dir().'/nyaa_test_'.uniqid()]);
    }

    public function test_search_episode_returns_filtered_torrents(): void
    {
        Http::fake([
            '*' => Http::response($this->sampleRss, 200),
        ]);

        $nyaa = app(NyaaService::class);
        $results = $nyaa->searchEpisode(['Test Anime'], 1);

        $this->assertCount(1, $results);
        $this->assertStringContainsString('Erai-raws', $results[0]['title']);
        $this->assertStringContainsString('1080p', $results[0]['title']);
        $this->assertFalse(str_contains($results[0]['title'], '~'));
    }

    public function test_search_episode_with_multiple_names(): void
    {
        Http::fake([
            '*' => Http::response($this->sampleRss, 200),
        ]);

        $nyaa = app(NyaaService::class);
        $results = $nyaa->searchEpisode(['Test Anime', 'Test Anime Romaji'], 1);

        $this->assertNotEmpty($results);
        $this->assertStringContainsString('Erai-raws', $results[0]['title']);
    }

    public function test_parse_rss_extracts_fields(): void
    {
        Http::fake([
            '*' => Http::response($this->sampleRss, 200),
        ]);

        $nyaa = app(NyaaService::class);
        $results = $nyaa->searchEpisode(['Test Anime'], 1);

        $this->assertArrayHasKey('title', $results[0]);
        $this->assertArrayHasKey('link', $results[0]);
        $this->assertArrayHasKey('magnet', $results[0]);
        $this->assertArrayHasKey('info_hash', $results[0]);
        $this->assertArrayHasKey('seeders', $results[0]);
        $this->assertArrayHasKey('leechers', $results[0]);
        $this->assertArrayHasKey('size', $results[0]);
        $this->assertArrayHasKey('trusted', $results[0]);
        $this->assertEquals(375, $results[0]['seeders']);
        $this->assertTrue($results[0]['trusted']);
    }

    public function test_build_magnet_link(): void
    {
        $nyaa = app(NyaaService::class);
        $magnet = $nyaa->buildMagnetLink('fb52f88f106ccb015aa327d0e2bf0cba6f17ac81', '[Erai-raws] Test - 01');

        $this->assertStringContainsString('magnet:?xt=urn:btih:fb52f88f106ccb015aa327d0e2bf0cba6f17ac81', $magnet);
        $this->assertStringContainsString('dn=', $magnet);
    }

    public function test_search_returns_empty_on_failed_request(): void
    {
        Http::fake([
            '*' => Http::response('', 500),
        ]);

        $nyaa = app(NyaaService::class);
        $results = $nyaa->searchEpisode(['Test Anime'], 1);

        $this->assertEmpty($results);
    }

    public function test_search_multiple_uses_pool(): void
    {
        Http::fake([
            '*' => Http::response($this->sampleRss, 200),
        ]);

        $nyaa = app(NyaaService::class);
        $results = $nyaa->searchMultipleEpisodes(['Test Anime'], [1, 2]);

        $this->assertArrayHasKey(1, $results);
        $this->assertArrayHasKey(2, $results);
        $this->assertIsArray($results[1]);
        $this->assertIsArray($results[2]);
    }

    public function test_search_custom(): void
    {
        Http::fake([
            '*' => Http::response($this->sampleRss, 200),
        ]);

        $nyaa = app(NyaaService::class);
        $results = $nyaa->searchCustom('Test Anime 01 1080p');

        $this->assertNotEmpty($results);
    }
}
