<?php

namespace Tests\Feature;

use App\Services\QbittorrentService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QbittorrentServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'media.qbittorrent.url' => 'http://localhost:23552',
            'media.qbittorrent.user' => 'admin',
            'media.qbittorrent.password' => 'adminadmin',
        ]);
    }

    public function test_login_returns_true_on_success(): void
    {
        Http::fake([
            '*/api/v2/auth/login' => Http::response('Ok.', 200),
        ]);

        $qb = app(QbittorrentService::class);
        $result = $qb->login();

        $this->assertTrue($result);
        Http::assertSent(function ($request) {
            return $request->url() === 'http://localhost:23552/api/v2/auth/login'
                && $request->data()['username'] === 'admin'
                && $request->data()['password'] === 'adminadmin';
        });
    }

    public function test_login_returns_false_on_failure(): void
    {
        Http::fake([
            '*/api/v2/auth/login' => Http::response('Fails.', 200),
        ]);

        $qb = app(QbittorrentService::class);
        $result = $qb->login();

        $this->assertFalse($result);
    }

    public function test_login_returns_false_on_forbidden(): void
    {
        Http::fake([
            '*/api/v2/auth/login' => Http::response('', 403),
        ]);

        $qb = app(QbittorrentService::class);
        $result = $qb->login();

        $this->assertFalse($result);
    }

    public function test_add_magnet_sends_correct_params(): void
    {
        Http::fake([
            '*/api/v2/torrents/add' => Http::response('', 200),
        ]);

        $qb = app(QbittorrentService::class);
        $magnet = 'magnet:?xt=urn:btih:abc123&dn=Test';
        $result = $qb->addMagnet($magnet, '/home/jaimer/Media/Animes/Test');

        $this->assertTrue($result);
        Http::assertSent(function ($request) use ($magnet) {
            return $request->url() === 'http://localhost:23552/api/v2/torrents/add'
                && $request->data()['urls'] === $magnet
                && $request->data()['savepath'] === '/home/jaimer/Media/Animes/Test';
        });
    }

    public function test_add_magnet_without_savepath(): void
    {
        Http::fake([
            '*/api/v2/torrents/add' => Http::response('', 200),
        ]);

        $qb = app(QbittorrentService::class);
        $magnet = 'magnet:?xt=urn:btih:abc123&dn=Test';
        $result = $qb->addMagnet($magnet);

        $this->assertTrue($result);
        Http::assertSent(function ($request) use ($magnet) {
            return $request->url() === 'http://localhost:23552/api/v2/torrents/add'
                && $request->data()['urls'] === $magnet
                && ! isset($request->data()['savepath']);
        });
    }

    public function test_add_magnet_returns_false_on_failure(): void
    {
        Http::fake([
            '*/api/v2/torrents/add' => Http::response('', 500),
        ]);

        $qb = app(QbittorrentService::class);
        $result = $qb->addMagnet('magnet:?xt=urn:btih:abc123&dn=Test');

        $this->assertFalse($result);
    }

    public function test_get_torrents_returns_normalized_data(): void
    {
        Http::fake([
            '*/api/v2/torrents/info' => Http::response([
                [
                    'name' => 'Test Anime',
                    'hash' => 'abc123',
                    'magnet_uri' => 'magnet:?xt=urn:btih:abc123',
                    'size' => 1073741824,
                    'progress' => 0.5,
                    'dlspeed' => 1048576,
                    'upspeed' => 0,
                    'eta' => 3600,
                    'state' => 'downloading',
                    'category' => 'Anime',
                    'tags' => '',
                    'completed' => 536870912,
                    'num_seeds' => 42,
                    'num_leechs' => 5,
                    'ratio' => 0.0,
                    'save_path' => '/home/jaimer/Media/Animes/Test',
                    'added_on' => 1700000000,
                    'completion_on' => 0,
                ],
            ], 200),
        ]);

        $qb = app(QbittorrentService::class);
        $torrents = $qb->getTorrents();

        $this->assertCount(1, $torrents);
        $this->assertEquals('Test Anime', $torrents[0]['name']);
        $this->assertEquals('abc123', $torrents[0]['hash']);
        $this->assertEquals(1073741824, $torrents[0]['size']);
        $this->assertEquals(0.5, $torrents[0]['progress']);
        $this->assertEquals(1048576, $torrents[0]['dlspeed']);
        $this->assertEquals(0, $torrents[0]['upspeed']);
        $this->assertEquals(3600, $torrents[0]['eta']);
        $this->assertEquals('downloading', $torrents[0]['state']);
        $this->assertEquals(42, $torrents[0]['num_seeds']);
        $this->assertEquals(5, $torrents[0]['num_leechs']);
    }

    public function test_get_torrents_returns_empty_on_failure(): void
    {
        Http::fake([
            '*/api/v2/torrents/info' => Http::response('', 500),
        ]);

        $qb = app(QbittorrentService::class);
        $torrents = $qb->getTorrents();

        $this->assertEmpty($torrents);
    }

    public function test_get_torrents_with_filter(): void
    {
        Http::fake([
            '*/api/v2/torrents/info?filter=downloading' => Http::response([], 200),
        ]);

        $qb = app(QbittorrentService::class);
        $qb->getTorrents('downloading');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'filter=downloading');
        });
    }

    public function test_get_torrents_without_filter(): void
    {
        Http::fake([
            '*/api/v2/torrents/info' => Http::response([], 200),
        ]);

        $qb = app(QbittorrentService::class);
        $qb->getTorrents();

        Http::assertSent(function ($request) {
            return $request->url() === 'http://localhost:23552/api/v2/torrents/info'
                && ! str_contains($request->url(), 'filter=');
        });
    }

    public function test_pause_torrent_sends_hash(): void
    {
        Http::fake([
            '*/api/v2/torrents/pause' => Http::response('', 200),
        ]);

        $qb = app(QbittorrentService::class);
        $result = $qb->pauseTorrent('abc123');

        $this->assertTrue($result);
        Http::assertSent(function ($request) {
            return $request->url() === 'http://localhost:23552/api/v2/torrents/pause'
                && $request->data()['hashes'] === 'abc123';
        });
    }

    public function test_resume_torrent_sends_hash(): void
    {
        Http::fake([
            '*/api/v2/torrents/resume' => Http::response('', 200),
        ]);

        $qb = app(QbittorrentService::class);
        $result = $qb->resumeTorrent('abc123');

        $this->assertTrue($result);
        Http::assertSent(function ($request) {
            return $request->url() === 'http://localhost:23552/api/v2/torrents/resume'
                && $request->data()['hashes'] === 'abc123';
        });
    }

    public function test_delete_torrent_sends_hash(): void
    {
        Http::fake([
            '*/api/v2/torrents/delete' => Http::response('', 200),
        ]);

        $qb = app(QbittorrentService::class);
        $result = $qb->deleteTorrent('abc123');

        $this->assertTrue($result);
        Http::assertSent(function ($request) {
            return $request->url() === 'http://localhost:23552/api/v2/torrents/delete'
                && $request->data()['hashes'] === 'abc123'
                && $request->data()['deleteFiles'] === 'false';
        });
    }

    public function test_delete_torrent_with_files(): void
    {
        Http::fake([
            '*/api/v2/torrents/delete' => Http::response('', 200),
        ]);

        $qb = app(QbittorrentService::class);
        $result = $qb->deleteTorrent('abc123', true);

        $this->assertTrue($result);
        Http::assertSent(function ($request) {
            return $request->data()['deleteFiles'] === 'true';
        });
    }

    public function test_is_connected_checks_version_endpoint(): void
    {
        Http::fake([
            '*/api/v2/app/version' => Http::response('v4.6.0', 200),
        ]);

        $qb = app(QbittorrentService::class);
        $result = $qb->isConnected();

        $this->assertTrue($result);
        Http::assertSent(function ($request) {
            return $request->url() === 'http://localhost:23552/api/v2/app/version';
        });
    }

    public function test_auto_reauth_on_403(): void
    {
        $hasLoggedIn = false;

        Http::fake(function ($request) use (&$hasLoggedIn) {
            if (str_contains($request->url(), 'auth/login')) {
                $hasLoggedIn = true;

                return Http::response('Ok.', 200);
            }

            if (str_contains($request->url(), 'torrents/info')) {
                if (! $hasLoggedIn) {
                    return Http::response([], 403);
                }

                return Http::response([
                    [
                        'name' => 'Test',
                        'hash' => 'abc',
                        'progress' => 1.0,
                        'state' => 'completed',
                        'size' => 100,
                    ],
                ], 200);
            }

            return Http::response('', 404);
        });

        $qb = app(QbittorrentService::class);
        $torrents = $qb->getTorrents();

        $this->assertCount(1, $torrents);
        $this->assertEquals('Test', $torrents[0]['name']);
        $this->assertTrue($hasLoggedIn);
    }
}
