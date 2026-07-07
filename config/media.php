<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Media Library Paths
    |--------------------------------------------------------------------------
    |
    | Base paths where your series and movies are stored.
    | The scanner will traverse these folders looking for video files.
    |
    */

    'paths' => [
        'animes' => env('MEDIA_ANIMES_PATH', '/home/jaimer/Media/Animes'),
        'peliculas' => env('MEDIA_PELICULAS_PATH', '/home/jaimer/Media/Peliculas'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Valid Video Extensions
    |--------------------------------------------------------------------------
    |
    | The scanner will only detect files with these extensions.
    | Add more formats here if needed.
    |
    */

    'video_extensions' => ['mkv', 'mp4', 'avi', 'wmv', 'flv'],

    /*
    |--------------------------------------------------------------------------
    | TMDB (The Movie Database)
    |--------------------------------------------------------------------------
    |
    | Configuration for the TMDB API.
    | Register at https://www.themoviedb.org to obtain an API key.
    |
    */

    'tmdb' => [
        'api_key' => env('TMDB_API_KEY', ''),
        'language' => env('TMDB_LANGUAGE', 'es-ES'),
        'base_url' => 'https://api.themoviedb.org/3',
    ],

    /*
    |--------------------------------------------------------------------------
    | qBittorrent
    |--------------------------------------------------------------------------
    |
    | Connection settings for qBittorrent Web UI.
    | Default: http://localhost:8080
    |
    */

    'qbittorrent' => [
        'url' => env('QBITTORRENT_URL', 'http://localhost:23552'),
        'user' => env('QBITTORRENT_USER', 'admin'),
        'password' => env('QBITTORRENT_PASS', 'adminadmin'),
    ],

    /*
    |--------------------------------------------------------------------------
    | TMDB Cache
    |--------------------------------------------------------------------------
    |
    | Directory where TMDB API responses are stored as JSON.
    | This avoids making API calls every time the app is opened.
    |
    */

    'cache_path' => storage_path('app/cache/tmdb'),

];
