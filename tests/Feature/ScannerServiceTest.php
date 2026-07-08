<?php

namespace Tests\Feature;

use App\Services\ScannerService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ScannerServiceTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir().'/scanner_test_'.uniqid();
        File::makeDirectory($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tempDir);

        parent::tearDown();
    }

    public function test_creates_season_folder(): void
    {
        $scanner = app(ScannerService::class);

        $result = $scanner->createSeasonFolder($this->tempDir, 3);

        $this->assertTrue($result);
        $this->assertDirectoryExists($this->tempDir.'/Season 3');
    }

    public function test_returns_false_when_folder_already_exists(): void
    {
        File::makeDirectory($this->tempDir.'/Season 1', 0755, true);

        $scanner = app(ScannerService::class);

        $result = $scanner->createSeasonFolder($this->tempDir, 1);

        $this->assertFalse($result);
    }

    public function test_returns_false_when_path_does_not_exist(): void
    {
        $scanner = app(ScannerService::class);

        $result = $scanner->createSeasonFolder('/nonexistent/path', 1);

        $this->assertFalse($result);
    }

    public function test_creates_multiple_season_folders(): void
    {
        $scanner = app(ScannerService::class);

        $scanner->createSeasonFolder($this->tempDir, 1);
        $scanner->createSeasonFolder($this->tempDir, 2);
        $scanner->createSeasonFolder($this->tempDir, 3);

        $this->assertDirectoryExists($this->tempDir.'/Season 1');
        $this->assertDirectoryExists($this->tempDir.'/Season 2');
        $this->assertDirectoryExists($this->tempDir.'/Season 3');
    }
}
