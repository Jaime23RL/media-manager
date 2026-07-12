<?php

namespace Tests\Feature;

use App\Services\RenamerService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class RenamerServiceTest extends TestCase
{
    private string $scriptPath = '/home/jaimer/scripts/renombrar/renombrar_serie.py';

    public function test_rename_returns_error_when_script_not_found(): void
    {
        $service = new RenamerService;
        // Override script path to a nonexistent file via reflection
        $ref = new \ReflectionClass($service);
        $prop = $ref->getProperty('scriptPath');
        $prop->setAccessible(true);
        $prop->setValue($service, '/nonexistent/script.py');

        $result = $service->rename('/tmp');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['output']);
        $this->assertEquals(0, $result['renamed']);
    }

    public function test_resolve_series_path_from_season_folder(): void
    {
        $tempDir = sys_get_temp_dir().'/renamer_test_'.uniqid();
        mkdir($tempDir.'/Test/Season 1', 0755, true);

        $service = new RenamerService;
        $result = $service->resolveSeriesPath($tempDir.'/Test/Season 1');

        $this->assertEquals($tempDir.'/Test', $result);

        File::deleteDirectory($tempDir);
    }

    public function test_resolve_series_path_from_series_folder(): void
    {
        $tempDir = sys_get_temp_dir().'/renamer_test_'.uniqid();
        mkdir($tempDir.'/Test', 0755, true);

        $service = new RenamerService;
        $result = $service->resolveSeriesPath($tempDir.'/Test');

        $this->assertEquals($tempDir.'/Test', $result);

        File::deleteDirectory($tempDir);
    }

    public function test_resolve_series_path_returns_null_for_invalid(): void
    {
        $service = new RenamerService;
        $result = $service->resolveSeriesPath('/nonexistent/path');

        $this->assertNull($result);
    }

    public function test_parse_renamed_count(): void
    {
        $service = new RenamerService;
        $output = "  ✔ File1.mkv\n  ✔ File2.mkv\n\n✔ 2 archivo(s) renombrado(s).";
        $result = $this->invokePrivate($service, 'parseRenamedCount', [$output]);

        // The regex matches lines starting with whitespace + ✔ (not the summary line)
        $this->assertEquals(2, $result);
    }

    public function test_parse_correct_count(): void
    {
        $service = new RenamerService;
        $output = 'Renombrar: 0 archivo(s)  |  Ya correctos: 3';
        $result = $this->invokePrivate($service, 'parseCorrectCount', [$output]);

        $this->assertEquals(3, $result);
    }

    public function test_parse_warning_count(): void
    {
        $service = new RenamerService;
        $output = "\n  ⚠ Ignorados (2):";
        $result = $this->invokePrivate($service, 'parseWarningCount', [$output]);

        $this->assertEquals(2, $result);
    }

    private function invokePrivate(object $object, string $method, array $args): mixed
    {
        $ref = new \ReflectionMethod($object, $method);
        $ref->setAccessible(true);

        return $ref->invoke($object, ...$args);
    }
}
