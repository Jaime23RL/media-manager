<?php

namespace App\Livewire;

use App\Services\ScannerService;
use Livewire\Component;

class ScanPage extends Component
{
    public $series = [];

    public $scanning = false;

    public $scanned = false;

    public $lastScanTime = null;

    public $loadedFromCache = false;

    public function boot(): void
    {
        $scanner = app(ScannerService::class);
        $this->lastScanTime = $scanner->getLastScanTime('animes');
    }

    public function scan(): void
    {
        $this->scanning = true;
        $this->loadedFromCache = false;

        $scanner = app(ScannerService::class);
        $this->series = $scanner->scan();

        // Save results to JSON
        $scanner->saveScan($this->series, 'animes');
        $this->lastScanTime = $scanner->getLastScanTime('animes');

        $this->scanning = false;
        $this->scanned = true;
    }

    public function loadPreviousScan(): void
    {
        $scanner = app(ScannerService::class);
        $data = $scanner->loadScan('animes');

        if ($data) {
            $this->series = $data['series'] ?? [];
            $this->lastScanTime = $data['scanned_at'] ?? null;
            $this->loadedFromCache = true;
            $this->scanned = true;
        }
    }

    public function render()
    {
        return view('livewire.scan-page');
    }
}
