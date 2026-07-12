<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;

class RenamerService
{
    private string $scriptPath;

    public function __construct()
    {
        $this->scriptPath = '/home/jaimer/scripts/renombrar/renombrar_serie.py';
    }

    /**
     * Run the Python renamer script on a series directory.
     *
     * @return array{success: bool, renamed: int, already_correct: int, warnings_count: int, output: string}
     */
    public function rename(string $seriesPath): array
    {
        if (! file_exists($this->scriptPath)) {
            return [
                'success' => false,
                'renamed' => 0,
                'already_correct' => 0,
                'warnings_count' => 0,
                'output' => 'Renamer script not found: '.$this->scriptPath,
            ];
        }

        $result = Process::run([
            'python3',
            $this->scriptPath,
            '-y',
            $seriesPath,
        ]);

        $output = $result->output();

        return [
            'success' => $result->successful(),
            'renamed' => $this->parseRenamedCount($output),
            'already_correct' => $this->parseCorrectCount($output),
            'warnings_count' => $this->parseWarningCount($output),
            'output' => $output,
        ];
    }

    /**
     * Resolve a save path to the series root directory.
     * If the path ends with "Season X", return the parent directory.
     */
    public function resolveSeriesPath(string $savePath): ?string
    {
        $path = rtrim($savePath, '/');

        if (preg_match('/Season\s+\d+$/i', basename($path))) {
            $seriesPath = dirname($path);

            return is_dir($seriesPath) ? $seriesPath : null;
        }

        return is_dir($path) ? $path : null;
    }

    /**
     * Count renamed files from the script output.
     */
    private function parseRenamedCount(string $output): int
    {
        // Count lines with leading whitespace + ✔ (indented rename lines, not the summary line)
        preg_match_all('/^[ \t]+✔\s+(.+)$/m', $output, $matches);

        return count($matches[1]);
    }

    /**
     * Parse "Ya correctos: X" from output.
     */
    private function parseCorrectCount(string $output): int
    {
        if (preg_match('/Ya correctos:\s*(\d+)/', $output, $m)) {
            return (int) $m[1];
        }

        return 0;
    }

    /**
     * Parse warning count from "⚠ Ignorados (X):".
     */
    private function parseWarningCount(string $output): int
    {
        if (preg_match('/Ignorados\s*\((\d+)\)/', $output, $m)) {
            return (int) $m[1];
        }

        return 0;
    }
}
