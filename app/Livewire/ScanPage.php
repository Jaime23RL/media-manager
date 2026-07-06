<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\ScannerService;

class ScanPage extends Component
{
    public $series = [];
    public $scanning = false;
    public $scanned = false;

    public function scan()
    {
        $this->scanning = true;
        $this->series = app(ScannerService::class)->scan();
        $this->scanning = false;
        $this->scanned = true;
    }

    public function render()
    {
        return view('livewire.scan-page');
    }
}
