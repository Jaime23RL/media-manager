<?php

namespace App\Livewire;

use App\Services\QbittorrentService;
use Livewire\Attributes\Isolate;
use Livewire\Component;

#[Isolate]
class DownloadsPage extends Component
{
    public array $torrents = [];

    public bool $loading = true;

    public string $filter = 'all';

    public ?string $error = null;

    public string $toastMessage = '';

    public string $toastType = '';

    public function boot(): void
    {
        $this->loadTorrents();
    }

    public function loadTorrents(): void
    {
        try {
            $qb = app(QbittorrentService::class);
            $this->torrents = $qb->getTorrents($this->filter === 'all' ? null : $this->filter);
            $this->error = null;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->torrents = [];
        } finally {
            $this->loading = false;
        }
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->loadTorrents();
    }

    public function pause(string $hash): void
    {
        $qb = app(QbittorrentService::class);
        if ($qb->pauseTorrent($hash)) {
            $this->showToast('Torrent paused', 'success');
        } else {
            $this->showToast('Failed to pause torrent', 'error');
        }
        $this->loadTorrents();
    }

    public function resume(string $hash): void
    {
        $qb = app(QbittorrentService::class);
        if ($qb->resumeTorrent($hash)) {
            $this->showToast('Torrent resumed', 'success');
        } else {
            $this->showToast('Failed to resume torrent', 'error');
        }
        $this->loadTorrents();
    }

    public function delete(string $hash): void
    {
        $qb = app(QbittorrentService::class);
        if ($qb->deleteTorrent($hash)) {
            $this->showToast('Torrent removed from queue', 'success');
        } else {
            $this->showToast('Failed to remove torrent', 'error');
        }
        $this->loadTorrents();
    }

    public function getStateLabel(string $state): string
    {
        return match ($state) {
            'downloading' => 'Downloading',
            'pausedDL', 'pausedUP' => 'Paused',
            'queuedDL', 'queuedUP' => 'Queued',
            'uploading' => 'Seeding',
            'stalledUP', 'stalledDL' => 'Stalled',
            'checkingUP', 'checkingDL' => 'Checking',
            'error' => 'Error',
            'missingFiles' => 'Missing Files',
            default => 'Unknown',
        };
    }

    public function getStateColor(string $state): string
    {
        return match ($state) {
            'downloading' => 'blue',
            'pausedDL', 'pausedUP' => 'amber',
            'queuedDL', 'queuedUP' => 'zinc',
            'uploading' => 'green',
            'stalledUP', 'stalledDL' => 'orange',
            'checkingUP', 'checkingDL' => 'purple',
            'error', 'missingFiles' => 'red',
            default => 'gray',
        };
    }

    public function formatSpeed(int $bytesPerSecond): string
    {
        if ($bytesPerSecond === 0) {
            return '0 B/s';
        }

        return $this->formatBytes($bytesPerSecond).'/s';
    }

    /**
     * Format bytes to human readable string without requiring intl extension.
     */
    public function formatBytes(int $bytes): string
    {
        $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB'];
        $unitIndex = 0;
        $size = $bytes;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2).' '.$units[$unitIndex];
    }

    public function formatEta(int $seconds): string
    {
        if ($seconds < 0) {
            return 'N/A';
        }

        if ($seconds < 60) {
            return $seconds.'s';
        }

        if ($seconds < 3600) {
            return floor($seconds / 60).'m';
        }

        if ($seconds < 86400) {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);

            return $hours.'h '.$minutes.'m';
        }

        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);

        return $days.'d '.$hours.'h';
    }

    public function isPaused(string $state): bool
    {
        return $state === 'pausedDL' || $state === 'pausedUP';
    }

    public function isActive(string $state): bool
    {
        return $state === 'downloading' || $state === 'uploading';
    }

    private function showToast(string $message, string $type): void
    {
        $this->toastMessage = $message;
        $this->toastType = $type;
    }

    public function clearToast(): void
    {
        $this->toastMessage = '';
    }

    public function render()
    {
        return view('livewire.downloads-page');
    }
}
