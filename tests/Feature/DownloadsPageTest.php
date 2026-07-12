<?php

namespace Tests\Feature;

use App\Livewire\DownloadsPage;
use App\Services\QbittorrentService;
use Livewire\Livewire;
use Tests\TestCase;

class DownloadsPageTest extends TestCase
{
    private function createMockService(array $torrents): QbittorrentService
    {
        $mock = $this->createMock(QbittorrentService::class);
        $mock->method('getTorrents')->willReturn($torrents);
        $mock->method('pauseTorrent')->willReturn(true);
        $mock->method('resumeTorrent')->willReturn(true);
        $mock->method('deleteTorrent')->willReturn(true);

        return $mock;
    }

    public function test_renders_with_torrents(): void
    {
        $torrents = [
            [
                'name' => 'Test Anime - 01',
                'hash' => 'abc123',
                'size' => 1073741824,
                'progress' => 0.5,
                'dlspeed' => 1048576,
                'upspeed' => 0,
                'eta' => 3600,
                'state' => 'downloading',
                'completed' => 536870912,
                'num_seeds' => 42,
                'num_leechs' => 5,
                'save_path' => '/home/jaimer/Media/Animes/Test',
            ],
        ];

        app()->instance(QbittorrentService::class, $this->createMockService($torrents));

        Livewire::test(DownloadsPage::class)
            ->assertSet('torrents', $torrents)
            ->assertSet('loading', false)
            ->assertSee('Test Anime - 01')
            ->assertSee('50.0%')
            ->assertSee('Downloading');
    }

    public function test_shows_empty_state_when_no_torrents(): void
    {
        app()->instance(QbittorrentService::class, $this->createMockService([]));

        Livewire::test(DownloadsPage::class)
            ->assertSet('torrents', [])
            ->assertSee('No torrents')
            ->assertSee('Your download queue is empty');
    }

    public function test_filter_buttons_change_filter(): void
    {
        $service = $this->createMockService([]);
        app()->instance(QbittorrentService::class, $service);

        $component = Livewire::test(DownloadsPage::class);

        $component->assertSet('filter', 'all');

        $component->call('setFilter', 'downloading');
        $component->assertSet('filter', 'downloading');

        $component->call('setFilter', 'completed');
        $component->assertSet('filter', 'completed');

        $component->call('setFilter', 'paused');
        $component->assertSet('filter', 'paused');
    }

    public function test_filter_passes_to_service(): void
    {
        $filtersUsed = [];
        $service = $this->createMock(QbittorrentService::class);
        $service->method('getTorrents')->willReturnCallback(function (?string $filter) use (&$filtersUsed) {
            $filtersUsed[] = $filter;

            return [];
        });

        app()->instance(QbittorrentService::class, $service);

        Livewire::test(DownloadsPage::class)
            ->call('setFilter', 'downloading');

        $this->assertContains('downloading', $filtersUsed);
    }

    public function test_pause_action_calls_service(): void
    {
        $service = $this->createMock(QbittorrentService::class);
        $service->expects($this->once())
            ->method('pauseTorrent')
            ->with('hash123')
            ->willReturn(true);
        $service->method('getTorrents')->willReturn([]);

        app()->instance(QbittorrentService::class, $service);

        Livewire::test(DownloadsPage::class)
            ->call('pause', 'hash123')
            ->assertSet('toastMessage', 'Torrent paused')
            ->assertSet('toastType', 'success');
    }

    public function test_pause_action_shows_error_on_failure(): void
    {
        $service = $this->createMock(QbittorrentService::class);
        $service->expects($this->once())
            ->method('pauseTorrent')
            ->with('hash123')
            ->willReturn(false);
        $service->method('getTorrents')->willReturn([]);

        app()->instance(QbittorrentService::class, $service);

        Livewire::test(DownloadsPage::class)
            ->call('pause', 'hash123')
            ->assertSet('toastMessage', 'Failed to pause torrent')
            ->assertSet('toastType', 'error');
    }

    public function test_resume_action_calls_service(): void
    {
        $service = $this->createMock(QbittorrentService::class);
        $service->expects($this->once())
            ->method('resumeTorrent')
            ->with('hash123')
            ->willReturn(true);
        $service->method('getTorrents')->willReturn([]);

        app()->instance(QbittorrentService::class, $service);

        Livewire::test(DownloadsPage::class)
            ->call('resume', 'hash123')
            ->assertSet('toastMessage', 'Torrent resumed')
            ->assertSet('toastType', 'success');
    }

    public function test_delete_action_calls_service(): void
    {
        $service = $this->createMock(QbittorrentService::class);
        $service->expects($this->once())
            ->method('deleteTorrent')
            ->with('hash123', false)
            ->willReturn(true);
        $service->method('getTorrents')->willReturn([]);

        app()->instance(QbittorrentService::class, $service);

        Livewire::test(DownloadsPage::class)
            ->call('delete', 'hash123')
            ->assertSet('toastMessage', 'Torrent removed from queue')
            ->assertSet('toastType', 'success');
    }

    public function test_delete_action_shows_error_on_failure(): void
    {
        $service = $this->createMock(QbittorrentService::class);
        $service->expects($this->once())
            ->method('deleteTorrent')
            ->with('hash123', false)
            ->willReturn(false);
        $service->method('getTorrents')->willReturn([]);

        app()->instance(QbittorrentService::class, $service);

        Livewire::test(DownloadsPage::class)
            ->call('delete', 'hash123')
            ->assertSet('toastMessage', 'Failed to remove torrent')
            ->assertSet('toastType', 'error');
    }

    public function test_error_state_when_service_throws(): void
    {
        $mock = $this->createMock(QbittorrentService::class);
        $mock->method('getTorrents')->willThrowException(new \Exception('Connection refused'));

        app()->instance(QbittorrentService::class, $mock);

        Livewire::test(DownloadsPage::class)
            ->assertSet('error', 'Connection refused')
            ->assertSet('torrents', [])
            ->assertSee('Connection Error');
    }

    public function test_clear_toast_clears_message(): void
    {
        app()->instance(QbittorrentService::class, $this->createMockService([]));

        Livewire::test(DownloadsPage::class)
            ->set('toastMessage', 'Test message')
            ->call('clearToast')
            ->assertSet('toastMessage', '');
    }

    public function test_state_helpers(): void
    {
        $component = Livewire::test(DownloadsPage::class);

        $this->assertEquals('Downloading', $component->instance()->getStateLabel('downloading'));
        $this->assertEquals('Paused', $component->instance()->getStateLabel('pausedDL'));
        $this->assertEquals('Seeding', $component->instance()->getStateLabel('uploading'));
        $this->assertEquals('blue', $component->instance()->getStateColor('downloading'));
        $this->assertEquals('green', $component->instance()->getStateColor('uploading'));
        $this->assertTrue($component->instance()->isPaused('pausedDL'));
        $this->assertFalse($component->instance()->isPaused('downloading'));
        $this->assertTrue($component->instance()->isActive('downloading'));
        $this->assertTrue($component->instance()->isActive('uploading'));
    }

    public function test_format_speed(): void
    {
        $component = Livewire::test(DownloadsPage::class);

        $this->assertEquals('0 B/s', $component->instance()->formatSpeed(0));
        $this->assertStringContainsString('MiB', $component->instance()->formatSpeed(1048576));
    }

    public function test_format_eta(): void
    {
        $component = Livewire::test(DownloadsPage::class);

        $this->assertEquals('N/A', $component->instance()->formatEta(-1));
        $this->assertEquals('30s', $component->instance()->formatEta(30));
        $this->assertEquals('5m', $component->instance()->formatEta(300));
        $this->assertEquals('1h 30m', $component->instance()->formatEta(5400));
        $this->assertEquals('2d 5h', $component->instance()->formatEta(190800));
    }
}
