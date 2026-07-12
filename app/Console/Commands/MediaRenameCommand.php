<?php

namespace App\Console\Commands;

use App\Services\RenamerService;
use Illuminate\Console\Command;

class MediaRenameCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'media:rename {path : Path to a series or season folder}';

    /**
     * The console command description.
     */
    protected $description = 'Rename media files in a series directory using the renamer script';

    /**
     * Execute the console command.
     */
    public function handle(RenamerService $renamer): int
    {
        $path = $this->argument('path');
        $seriesPath = $renamer->resolveSeriesPath($path);

        if ($seriesPath === null) {
            $this->error("Invalid series path: {$path}");

            return self::FAILURE;
        }

        $this->info("Renaming files in: {$seriesPath}");

        $result = $renamer->rename($seriesPath);

        if (! $result['success']) {
            $this->error('Renaming failed.');
            if ($result['output'] !== '') {
                $this->line($result['output']);
            }

            return self::FAILURE;
        }

        if ($result['renamed'] > 0) {
            $this->info("Renamed {$result['renamed']} file(s).");
        }

        if ($result['already_correct'] > 0) {
            $this->info("{$result['already_correct']} file(s) already correctly named.");
        }

        if ($result['warnings_count'] > 0) {
            $this->warn("{$result['warnings_count']} file(s) could not be parsed.");
        }

        if ($result['output'] !== '') {
            $this->line($result['output']);
        }

        return self::SUCCESS;
    }
}
