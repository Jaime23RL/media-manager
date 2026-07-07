<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class ScannerService
{
    /**
     * Scan media folders and return found series.
     */
    public function scan(): array
    {
        $paths = config('media.paths');
        $videoExtensions = config('media.video_extensions');
        $series = [];

        // Scan each configured path (Animes, Movies)
        foreach ($paths as $type => $path) {
            if (! File::isDirectory($path)) {
                continue;
            }

            // Get subdirectories (each folder = one series)
            $directories = File::directories($path);

            foreach ($directories as $directory) {
                $serieName = basename($directory);
                $files = $this->getVideoFiles($directory, $videoExtensions);

                if (count($files) > 0) {
                    $series[] = [
                        'name' => $serieName,
                        'path' => $directory,
                        'files' => $files,
                        'type' => $type,
                        'file_count' => count($files),
                    ];
                }
            }
        }

        return $series;
    }

    /**
     * Get video files from a directory (recursive).
     */
    private function getVideoFiles(string $directory, array $extensions): array
    {
        $files = [];

        // Check files in current directory
        $items = File::files($directory);

        foreach ($items as $item) {
            if (in_array(strtolower($item->getExtension()), $extensions)) {
                $files[] = $item->getFilename();
            }
        }

        // Check subdirectories (e.g., Season 1, Season 2)
        $subdirectories = File::directories($directory);

        foreach ($subdirectories as $subdirectory) {
            $subFiles = File::files($subdirectory);

            foreach ($subFiles as $file) {
                if (in_array(strtolower($file->getExtension()), $extensions)) {
                    $files[] = basename($subdirectory).'/'.$file->getFilename();
                }
            }
        }

        sort($files);

        return $files;
    }

    /**
     * Save scan results to a JSON file.
     *
     * @param  string  $type  'animes' or 'peliculas'
     */
    public function saveScan(array $series, string $type): void
    {
        $cachePath = storage_path('app/cache/local');

        if (! File::isDirectory($cachePath)) {
            File::makeDirectory($cachePath, 0755, true);
        }

        $data = [
            'scanned_at' => now()->toISOString(),
            'path' => config("media.paths.{$type}"),
            'series' => $series,
        ];

        $filePath = $cachePath.'/'.$type.'.json';
        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Load previously scanned results from JSON file.
     *
     * @param  string  $type  'animes' or 'peliculas'
     */
    public function loadScan(string $type): ?array
    {
        $filePath = storage_path("app/cache/local/{$type}.json");

        if (! file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);
        $data = json_decode($content, true);

        return $data ?? null;
    }

    /**
     * Get the timestamp of the last scan.
     *
     * @param  string  $type  'animes' or 'peliculas'
     * @return string|null ISO timestamp or null if no scan exists
     */
    public function getLastScanTime(string $type): ?string
    {
        $data = $this->loadScan($type);

        return $data['scanned_at'] ?? null;
    }

    /**
     * Update a single series in the scan cache with TMDB data.
     *
     * @param  string  $type  'animes' or 'peliculas'
     * @param  int  $index  Series index in the array
     * @param  array  $enrichedData  Series data with TMDB fields
     */
    public function updateSerie(string $type, int $index, array $enrichedData): void
    {
        $data = $this->loadScan($type);

        if (! $data || ! isset($data['series'][$index])) {
            return;
        }

        $data['series'][$index] = array_merge($data['series'][$index], $enrichedData);

        $filePath = storage_path("app/cache/local/{$type}.json");
        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
    }
}
