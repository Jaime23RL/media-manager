<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // Media Manager
    Route::get('/scan', \App\Livewire\ScanPage::class)->name('scan');
    Route::get('/series', \App\Livewire\SeriesPage::class)->name('series');
    Route::get('/series/{id}', \App\Livewire\SerieDetailPage::class)->name('series.show');
    Route::get('/downloads', \App\Livewire\DownloadsPage::class)->name('downloads');
    Route::get('/settings/media', \App\Livewire\SettingsPage::class)->name('settings.media');
});

require __DIR__.'/settings.php';
