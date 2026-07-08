<?php

use App\Livewire\AddAnimePage;
use App\Livewire\DownloadsPage;
use App\Livewire\ScanPage;
use App\Livewire\SerieDetailPage;
use App\Livewire\SeriesPage;
use App\Livewire\SettingsPage;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // Media Manager
    Route::get('/scan', ScanPage::class)->name('scan');
    Route::get('/add-anime', AddAnimePage::class)->name('add-anime');
    Route::get('/series', SeriesPage::class)->name('series');
    Route::get('/series/{id}', SerieDetailPage::class)->name('series.show');
    Route::get('/downloads', DownloadsPage::class)->name('downloads');
    Route::get('/settings/media', SettingsPage::class)->name('settings.media');
});

require __DIR__.'/settings.php';
