<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class ScannerService
{
    /**
     * Escanea las carpetas de media y retorna series encontradas.
     *
     * @return array
     */
    public function scan(): array
    {
        $paths = config('media.paths');
        $videoExtensions = config('media.video_extensions');
        $series = [];

        // Escanear cada ruta configurada (Animes, Películas)
        foreach ($paths as $type => $path) {
            if (!File::isDirectory($path)) {
                continue;
            }

            // Obtener subcarpetas (cada carpeta = una serie)
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
     * Obtiene archivos de video de un directorio (recursivo).
     *
     * @param string $directory
     * @param array $extensions
     * @return array
     */
    private function getVideoFiles(string $directory, array $extensions): array
    {
        $files = [];

        // Buscar archivos en el directorio actual
        $items = File::files($directory);

        foreach ($items as $item) {
            if (in_array(strtolower($item->getExtension()), $extensions)) {
                $files[] = $item->getFilename();
            }
        }

        // Buscar en subcarpetas (ej: Season 1, Season 2)
        $subdirectories = File::directories($directory);

        foreach ($subdirectories as $subdirectory) {
            $subFiles = File::files($subdirectory);

            foreach ($subFiles as $file) {
                if (in_array(strtolower($file->getExtension()), $extensions)) {
                    $files[] = basename($subdirectory) . '/' . $file->getFilename();
                }
            }
        }

        sort($files);

        return $files;
    }
}
