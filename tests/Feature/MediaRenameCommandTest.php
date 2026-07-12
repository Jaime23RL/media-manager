<?php

namespace Tests\Feature;

use App\Console\Commands\MediaRenameCommand;
use App\Services\RenamerService;
use Tests\TestCase;

class MediaRenameCommandTest extends TestCase
{
    public function test_command_renames_series_from_season_folder(): void
    {
        $mock = $this->createMock(RenamerService::class);
        $mock->expects($this->once())
            ->method('resolveSeriesPath')
            ->with('/home/jaimer/Media/Animes/Test/Season 1')
            ->willReturn('/home/jaimer/Media/Animes/Test');
        $mock->expects($this->once())
            ->method('rename')
            ->with('/home/jaimer/Media/Animes/Test')
            ->willReturn([
                'success' => true,
                'renamed' => 2,
                'already_correct' => 1,
                'warnings_count' => 0,
                'output' => 'test output',
            ]);

        app()->instance(RenamerService::class, $mock);

        $this->artisan(MediaRenameCommand::class, ['path' => '/home/jaimer/Media/Animes/Test/Season 1'])
            ->expectsOutputToContain('Renaming files in: /home/jaimer/Media/Animes/Test')
            ->expectsOutputToContain('Renamed 2 file(s).')
            ->expectsOutputToContain('1 file(s) already correctly named.')
            ->assertSuccessful();
    }

    public function test_command_renames_series_from_series_folder(): void
    {
        $mock = $this->createMock(RenamerService::class);
        $mock->expects($this->once())
            ->method('resolveSeriesPath')
            ->with('/home/jaimer/Media/Animes/Test')
            ->willReturn('/home/jaimer/Media/Animes/Test');
        $mock->expects($this->once())
            ->method('rename')
            ->willReturn([
                'success' => true,
                'renamed' => 0,
                'already_correct' => 3,
                'warnings_count' => 0,
                'output' => '',
            ]);

        app()->instance(RenamerService::class, $mock);

        $this->artisan(MediaRenameCommand::class, ['path' => '/home/jaimer/Media/Animes/Test'])
            ->expectsOutputToContain('Renaming files in: /home/jaimer/Media/Animes/Test')
            ->expectsOutputToContain('3 file(s) already correctly named.')
            ->assertSuccessful();
    }

    public function test_command_fails_on_invalid_path(): void
    {
        $mock = $this->createMock(RenamerService::class);
        $mock->expects($this->once())
            ->method('resolveSeriesPath')
            ->with('/nonexistent')
            ->willReturn(null);

        app()->instance(RenamerService::class, $mock);

        $this->artisan(MediaRenameCommand::class, ['path' => '/nonexistent'])
            ->expectsOutputToContain('Invalid series path: /nonexistent')
            ->assertFailed();
    }

    public function test_command_fails_when_renaming_fails(): void
    {
        $mock = $this->createMock(RenamerService::class);
        $mock->expects($this->once())
            ->method('resolveSeriesPath')
            ->willReturn('/home/jaimer/Media/Animes/Test');
        $mock->expects($this->once())
            ->method('rename')
            ->willReturn([
                'success' => false,
                'renamed' => 0,
                'already_correct' => 0,
                'warnings_count' => 0,
                'output' => 'error output',
            ]);

        app()->instance(RenamerService::class, $mock);

        $this->artisan(MediaRenameCommand::class, ['path' => '/home/jaimer/Media/Animes/Test'])
            ->expectsOutputToContain('Renaming failed.')
            ->assertFailed();
    }

    public function test_command_shows_warnings(): void
    {
        $mock = $this->createMock(RenamerService::class);
        $mock->expects($this->once())
            ->method('resolveSeriesPath')
            ->willReturn('/home/jaimer/Media/Animes/Test');
        $mock->expects($this->once())
            ->method('rename')
            ->willReturn([
                'success' => true,
                'renamed' => 1,
                'already_correct' => 0,
                'warnings_count' => 2,
                'output' => '',
            ]);

        app()->instance(RenamerService::class, $mock);

        $this->artisan(MediaRenameCommand::class, ['path' => '/home/jaimer/Media/Animes/Test'])
            ->expectsOutputToContain('2 file(s) could not be parsed.')
            ->assertSuccessful();
    }
}
