<?php

namespace App\Services;

class NamingService
{
    /**
     * Patrones de regex para extraer temporada y episodio del nombre del archivo.
     */
    private array $patterns = [
        // S01E05 - Formato estándar
        '/S(\d+)E(\d+)/i',
        // - 05 o - 05 - (después de un guión)
        '/\s-\s(\d+)\s/',
        // _E05_ o _Episode_05_
        '/[_\s]E(?:pisode)?[_\s]?(\d+)/i',
        // [05] o [Ep 05]
        '/\[(?:Ep(?:isode)?[_\s]*)?(\d+)\]/i',
        // (1920x1080) - ignorar, no es episodio
        '/\(\d+x\d+\)/i',
    ];

    /**
     * Extrae temporada y episodio del nombre del archivo.
     *
     * @param string $filename
     * @return array|null ['season' => int, 'episode' => int] o null si no se puede parsear
     */
    public function parse(string $filename): ?array
    {
        // Limpiar nombre del archivo
        $name = pathinfo($filename, PATHINFO_FILENAME);

        // Intentar patrón S01E05 primero (más específico)
        if (preg_match('/S(\d+)E(\d+)/i', $name, $matches)) {
            return [
                'season' => (int) $matches[1],
                'episode' => (int) $matches[2],
            ];
        }

        // Intentar otros patrones
        foreach ($this->patterns as $pattern) {
            if (preg_match($pattern, $name, $matches)) {
                // Si solo captura un número, asumir temporada 1
                if (count($matches) === 2) {
                    return [
                        'season' => 1,
                        'episode' => (int) $matches[1],
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Normaliza un nombre de serie para comparación.
     * Elimina fansubs, grupos, y otros prefijos comunes.
     *
     * @param string $name
     * @return string
     */
    public function normalizeSerieName(string $name): string
    {
        // Elimitar prefijos de fansub entre corchetes
        $name = preg_replace('/^\[.*?\]\s*/i', '', $name);

        // Elimitar prefijos entre paréntesis
        $name = preg_replace('/^\(.*?\)\s*/i', '', $name);

        // Eliminar guiones y guiones bajos al inicio
        $name = preg_replace('/^[\s_-]+/', '', $name);

        // Reemplazar guiones bajos y guiones por espacios
        $name = str_replace(['_', '-'], ' ', $name);

        // Eliminar espacios múltiples
        $name = preg_replace('/\s+/', ' ', $name);

        return trim($name);
    }
}
