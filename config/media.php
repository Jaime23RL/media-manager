<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rutas de la librería multimedia
    |--------------------------------------------------------------------------
    |
    | Estas son las rutas base donde se encuentran tus series y películas.
    | El scanner va a recorrer estas carpetas buscando archivos de video.
    |
    */

    'paths' => [
        'animes' => env('MEDIA_ANIMES_PATH', '/home/jaimer/Media/Animes'),
        'peliculas' => env('MEDIA_PELICULAS_PATH', '/home/jaimer/Media/Peliculas'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Extensiones de video válidas
    |--------------------------------------------------------------------------
    |
    | El scanner solo va a detectar archivos con estas extensiones.
    | Si tienes archivos en otros formatos, añádelos aquí.
    |
    */

    'video_extensions' => ['mkv', 'mp4', 'avi', 'wmv', 'flv'],

    /*
    |--------------------------------------------------------------------------
    | TMDB
    |--------------------------------------------------------------------------
    |
    | Configuración de la API de The Movie Database.
    | Necesitas registrarte en https://www.themoviedb.org para obtener una API key.
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
    | Configuración de conexión con qBittorrent Web UI.
    | Por defecto corre en http://localhost:8080.
    |
    */

    'qbittorrent' => [
        'url' => env('QBITTORRENT_URL', 'http://localhost:23552'),
        'user' => env('QBITTORRENT_USER', 'admin'),
        'password' => env('QBITTORRENT_PASS', 'adminadmin'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache de TMDB
    |--------------------------------------------------------------------------
    |
    | Directorio donde se guardan las respuestas de TMDB en JSON.
    | Esto evita hacer llamadas a la API cada vez que abres la app.
    |
    */

    'cache_path' => storage_path('app/cache/tmdb'),

];
