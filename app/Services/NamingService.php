<?php

namespace App\Services;

class NamingService
{
    /**
     * Regex patterns to extract season and episode from filename.
     */
    private array $patterns = [
        // S01E05 - Standard format
        '/S(\d+)E(\d+)/i',
        // - 05 or - 05 - (after a dash)
        '/\s-\s(\d+)\s/',
        // _E05_ or _Episode_05_
        '/[_\s]E(?:pisode)?[_\s]?(\d+)/i',
        // [05] or [Ep 05]
        '/\[(?:Ep(?:isode)?[_\s]*)?(\d+)\]/i',
        // (1920x1080) - ignore, not an episode
        '/\(\d+x\d+\)/i',
    ];

    /**
     * Extract season and episode from filename.
     *
     * @return array|null ['season' => int, 'episode' => int] or null if unparseable
     */
    public function parse(string $filename): ?array
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);

        // Try S01E05 pattern first (most specific)
        if (preg_match('/S(\d+)E(\d+)/i', $name, $matches)) {
            return [
                'season' => (int) $matches[1],
                'episode' => (int) $matches[2],
            ];
        }

        // Try other patterns
        foreach ($this->patterns as $pattern) {
            if (preg_match($pattern, $name, $matches)) {
                // If only one number captured, assume season 1
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
     * Normalize a series name for comparison.
     * Removes fansubs, groups, and other common prefixes.
     */
    public function normalizeSerieName(string $name): string
    {
        // Remove fansub prefixes in brackets
        $name = preg_replace('/^\[.*?\]\s*/i', '', $name);

        // Remove prefixes in parentheses
        $name = preg_replace('/^\(.*?\)\s*/i', '', $name);

        // Remove dashes and underscores at the start
        $name = preg_replace('/^[\s_-]+/', '', $name);

        // Replace underscores and dashes with spaces
        $name = str_replace(['_', '-'], ' ', $name);

        // Remove multiple spaces
        $name = preg_replace('/\s+/', ' ', $name);

        return trim($name);
    }
}
