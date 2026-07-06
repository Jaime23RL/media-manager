# Media Manager - Guia de Desarrollo

Aplicacion web local para gestionar una libreria multimedia, comparar con TMDB y descargar episodios faltantes via qBittorrent.

**Stack**: Laravel 13 + Livewire + Flux UI + SQLite

---

## 1. Instalacion del entorno

### 1.1 Instalar PHP, Composer y el instalador de Laravel

```bash
# CachyOS/Arch
sudo pacman -S php composer php-gd php-iconv php-pdo_mysql

# Instalar el instalador de Laravel
composer global require laravel/installer

# Añadir al PATH (si no lo tienes ya)
echo 'export PATH="$HOME/.config/composer/vendor/bin:$PATH"' >> ~/.zshrc
source ~/.zshrc

# Verificar
php --version
composer --version
laravel --version
```

### 1.2 Extensiones PHP necesarias

En `/etc/php/php.ini`, descomentar (quitar el `;`):
- `extension=iconv` (linea ~924)
- `extension=gd` (si no esta ya activo)

### 1.3 Crear proyecto con Livewire

```bash
cd ~
laravel new media-manager
# Seleccionar:
#   - Starter kit: Livewire
#   - Auth provider: Laravel's built-in authentication
#   - Single-file Livewire components: Yes
#   - Teams: No
#   - Testing: Pest
#   - Database: SQLite (por defecto)

# Instalar dependencias
cd ~/media-manager
npm install && npm run build

# Generar clave de encriptacion (si no se genero automaticamente)
php artisan key:generate

# Crear base de datos SQLite
touch database/database.sqlite

# Ejecutar migraciones
php artisan migrate

# Iniciar servidor
composer run dev
# Abrir http://localhost:8000
```

### 1.4 Extensiones PHP necesarias (si falla composer install)

```bash
# Buscar que falta
php -m | grep -i iconv

# Habilitar en php.ini si esta comentado
sudo sed -i 's/;extension=iconv/extension=iconv/' /etc/php/php.ini
```

---

## 2. Arquitectura de la aplicacion

### 2.1 Stack tecnologico

| Capa | Tecnologia | Version |
|------|-----------|---------|
| Backend | Laravel | 13.x |
| Frontend (interactividad) | Livewire | 4.x |
| Componentes UI | Flux UI | 2.x |
| CSS | Tailwind CSS | 4.x |
| Plantillas | Blade | - |
| Auth | Laravel Fortify | - |
| Base de datos | SQLite | - |
| Asset bundler | Vite | - |

### 2.2 Flujo principal

```
Usuario configura rutas base (~/Media/Animes, ~/Media/Peliculas)
        |
        v
[1] ScannerService escanea carpetas
    - Lee subcarpetas de Animes/ y Peliculas/
    - Identifica archivos de video (.mkv, .mp4, .avi, etc.)
    - Extrae nombre de serie del nombre de carpeta
        |
        v
[2] TmdbService busca serie en TMDB
    - Busca por nombre de carpeta
    - Obtiene lista de temporadas y episodios
    - Guarda cache en JSON (storage/app/cache/tmdb/{serie_id}.json)
        |
        v
[3] NamingService parsea nombres de archivos locales
    - Extrae temporada y numero de episodio del nombre
    - Ejemplo: "[Fansub] Serie S02E05.mkv" -> temporada 2, episodio 5
        |
        v
[4] CompareService cruza datos
    - Lista local vs lista TMDB
    - Identifica episodios faltantes
    - Devuelve lista de faltantes con info de TMDB
        |
        v
[5] Interfaz web Livewire muestra:
    - Series encontradas con estado (completa/incompleta)
    - Episodios faltantes por serie
    - Boton para buscar torrent en nyaa.si
    - Cola de descargas
        |
        v
[6] DownloadService gestiona descargas
    - Busca torrent en nyaa.si via su busqueda
    - Descarga archivo .torrent o magnet link
    - Envia a qBittorrent via Web API
    - Monitorea progreso
```

### 2.3 Dependencias externas

| Servicio | Tipo | URL / Documentacion |
|----------|------|---------------------|
| TMDB | API REST (gratuita, registro requerido) | https://developer.themoviedb.org/docs |
| nyaa.si | Web scraping | https://nyaa.si (no tiene API oficial, se scrapea) |
| qBittorrent | Web API (local) | https://github.com/qbittorrent/qBittorrent/wiki/WebUI-API-(qBittorrent-4.1) |

---

## 3. Estructura del proyecto

### 3.1 Estructura actual (Livewire starter kit)

```
media-manager/
├── app/
│   ├── Http/
│   │   └── Controllers/          # Vacio (Livewire no usa controladores)
│   ├── Livewire/
│   │   └── Actions/
│   │       └── Logout.php        # Componente de cerrar sesion
│   ├── Models/                   # Modelos Eloquent
│   └── Providers/                # Service providers
├── config/                       # Configuracion de Laravel
├── resources/
│   └── views/
│       ├── components/           # Componentes Blade reutilizables
│       ├── layouts/              # Layouts (app, auth)
│       ├── pages/                # Paginas Blade
│       │   ├── auth/             # Login, registro, etc.
│       │   └── settings/         # Configuracion de usuario
│       ├── flux/                 # Componentes Flux customizados
│       ├── partials/             # Parciales reutilizables
│       ├── dashboard.blade.php   # Dashboard principal
│       └── welcome.blade.php     # Pagina de inicio
├── routes/
│   └── web.php                   # Rutas de la aplicacion
├── database/
│   └── database.sqlite           # Base de datos SQLite
└── .env                          # Configuracion
```

### 3.2 Estructura a crear para la app

```
media-manager/
├── app/
│   ├── Livewire/
│   │   ├── ScanPage.php              # Componente: escaneo de carpetas
│   │   ├── SeriesPage.php            # Componente: lista de series
│   │   ├── SerieDetailPage.php       # Componente: detalle + episodios
│   │   ├── DownloadsPage.php         # Componente: cola de descargas
│   │   └── SettingsPage.php          # Componente: configuracion
│   ├── Services/
│   │   ├── ScannerService.php        # Escaneo del filesystem
│   │   ├── TmdbService.php           # Comunicacion con TMDB API
│   │   ├── NamingService.php         # Parseo de nombres de archivos
│   │   ├── CompareService.php        # Comparacion local vs TMDB
│   │   ├── NyaaService.php           # Busqueda en nyaa.si
│   │   └── QbittorrentService.php    # Comunicacion con qBittorrent
│   └── Models/
│       └── Setting.php               # Modelo para configuracion (opcional)
├── resources/views/
│   ├── livewire/
│   │   ├── scan-page.blade.php       # Vista del escaneo
│   │   ├── series-page.blade.php     # Vista de lista series
│   │   ├── serie-detail-page.blade.php # Vista detalle serie
│   │   ├── downloads-page.blade.php  # Vista descargas
│   │   └── settings-page.blade.php   # Vista configuracion
│   └── layouts/
│       └── app.blade.php             # Layout principal (ya existe)
├── config/
│   └── media.php                     # Configuracion de rutas y APIs
├── storage/
│   └── app/
│       └── cache/
│           └── tmdb/                 # Cache de TMDB en JSON
└── .env                              # API keys y configuracion
```

### 3.3 Rutas a definir

```php
// routes/web.php

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // Rutas de media manager
    Route::get('/scan', \App\Livewire\ScanPage::class)->name('scan');
    Route::get('/series', \App\Livewire\SeriesPage::class)->name('series');
    Route::get('/series/{id}', \App\Livewire\SerieDetailPage::class)->name('series.show');
    Route::get('/downloads', \App\Livewire\DownloadsPage::class)->name('downloads');
    Route::get('/settings/media', \App\Livewire\SettingsPage::class)->name('settings.media');
});

require __DIR__.'/settings.php';
```

---

## 4. Servicios - Logica de negocio

### 4.1 ScannerService

**Responsabilidad**: Escanear carpetas y detectar series + archivos de video.

```php
// Funcion principal que debes implementar
public function scan(string $basePath): array
{
    // 1. Recorrer ~/Media/Animes y ~/Media/Peliculas
    // 2. Por cada subcarpeta:
    //    - Obtener nombre de la carpeta (= nombre de serie)
    //    - Buscar archivos de video (.mkv, .mp4, .avi, .wmv, .flv)
    //    - Guardar lista de archivos encontrados
    // 3. Devolver array con todas las series y sus archivos
}
```

**Extensiones de video a detectar**: `mkv`, `mp4`, `avi`, `wmv`, `flv`

**Resultado esperado**:
```php
[
    [
        'name' => 'Steins Gate',
        'path' => '/home/jaimer/Media/Animes/Steins Gate',
        'files' => ['[SubGroup] Steins Gate S01E01.mkv', '...'],
        'type' => 'anime'
    ],
    // ...
]
```

### 4.2 TmdbService

**Responsabilidad**: Comunicarse con la API de TMDB.

**Necesitas**:
- Registrar cuenta en https://www.themoviedb.org
- Obtener API Key en https://www.themoviedb.org/settings/api
- Guardarla en `.env` como `TMDB_API_KEY`

**Endpoints a usar**:
```
GET /search/tv?query={nombre}&language=es-ES
GET /tv/{id}?language=es-ES
GET /tv/{id}/season/{season}?language=es-ES
```

**Cache**: Guardar respuestas en `storage/app/cache/tmdb/{tmdb_id}.json` para no repetir llamadas.

### 4.3 NamingService

**Responsabilidad**: Parsear nombres de archivos para extraer temporada y episodio.

**Patrones comunes a detectar**:
```
[SubGroup] Steins Gate S01E05.mkv          -> S01E05
Steins;Gate - 05.mkv                        -> S01E05
Steins;Gate - [BD 1080p] - 05 (1280x720).mkv -> S01E05
[Coalgirls]_Steins_Gate_Episode_05_(1920x1080_Blu-ray_FLAC)_[8B9A6B2F].mkv -> Ep 05
```

**Estrategia**: Usar regex para capturar patrones como `S(\d+)E(\d+)`, ` - (\d+)`, `_E(\d+)`, etc.

### 4.4 CompareService

**Responsabilidad**: Cruzar archivos locales con lista de episodios de TMDB.

```php
public function compare(array $localEpisodes, array $tmdbEpisodes): array
{
    // 1. Normalizar ambos listados a formato comparable
    // 2. Encontrar interseccion (lo que tienes)
    // 3. Encontrar diferencia (lo que falta)
    // 4. Devolver:
    //    - 'have' => episodios que tienes
    //    - 'missing' => episodios que faltan
    //    - 'extra' => archivos locales que no existen en TMDB
}
```

### 4.5 NyaaService

**Responsabilidad**: Buscar torrents en nyaa.si.

**Enfoque**: nyaa.si no tiene API, pero se puede:
- Hacer HTTP request a `https://nyaa.si/?f=0&c=0_0&q={busqueda}&s=seeders&o=desc`
- Parsear el HTML para extraer: titulo, enlace torrent/magnet, seeds, size
- Usar **Guzzle** (ya incluido en Laravel) para las peticiones

**Notas**:
- Buscar por nombre de serie + numero de episodio
- Filtrar por idioma si es necesario (subs ingles es mas comun)
- Ordenar por seeds (mas seeds = mas rapido)

### 4.6 QbittorrentService

**Responsabilidad**: Enviar torrents a qBittorrent y monitorear estado.

**API de qBittorrent** (por defecto en `http://localhost:8080`):

```
POST /api/v2/auth/login          # Login (username/password)
POST /api/v2/torrents/add        # Anadir torrent (magnet o .torrent)
GET  /api/v2/torrents/info       # Lista de torrents y su estado
POST /api/v2/torrents/pause      # Pausar
POST /api/v2/torrents/resume     # Reanudar
POST /api/v2/torrents/delete     # Eliminar
```

**Configuracion**: Guardar en `.env`:
```
QBITTORRENT_URL=http://localhost:8080
QBITTORRENT_USER=admin
QBITTORRENT_PASS=adminadmin
```

---

## 5. Ejemplo de componente Livewire

### 5.1 Componente basico (clase)

```php
// app/Livewire/ScanPage.php
<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\ScannerService;

class ScanPage extends Component
{
    public $series = [];
    public $scanning = false;

    public function scan()
    {
        $this->scanning = true;
        $this->series = app(ScannerService::class)->scan();
        $this->scanning = false;
    }

    public function render()
    {
        return view('livewire.scan-page');
    }
}
```

### 5.2 Vista Blade del componente

```blade
{{-- resources/views/livewire/scan-page.blade.php --}}
<div>
    <h1 class="text-2xl font-bold mb-4">Escaneo de series</h1>

    <button wire:click="scan" wire:loading.attr="disabled"
            class="bg-blue-500 text-white px-4 py-2 rounded">
        Escanear carpetas
    </button>

    <div wire:loading>
        Escaneando carpetas...
    </div>

    @if(count($series) > 0)
        <div class="mt-4">
            @foreach($series as $serie)
                <div class="border p-2 mb-2 rounded">
                    {{ $serie['name'] }}
                </div>
            @endforeach
        </div>
    @endif
</div>
```

### 5.3 Directivas Livewire utiles

```blade
{{-- Ejecutar accion al hacer clic --}}
<button wire:click="scan">Escanear</button>

{{-- Actualizar campo automaticamente --}}
<input wire:model.live="search" placeholder="Buscar...">

{{-- Mostrar loading --}}
<div wire:loading>Cargando...</div>

{{-- Deshabilitar durante loading --}}
<button wire:click="save" wire:loading.attr="disabled">Guardar</button>

{{-- Confirmar antes de ejecutar --}}
<button wire:click="delete" wire:confirm="¿Estas seguro?">Borrar</button>

{{-- Pasar parametros --}}
<button wire:click="selectSerie({{ $serie['id'] }})">Ver</button>
```

---

## 6. Configuracion (.env)

```env
# TMDB
TMDB_API_KEY=tu_api_key_aqui
TMDB_LANGUAGE=es-ES

# qBittorrent
QBITTORRENT_URL=http://localhost:8080
QBITTORRENT_USER=admin
QBITTORRENT_PASS=adminadmin

# Rutas de media
MEDIA_ANIMES_PATH=/home/jaimer/Media/Animes
MEDIA_PELICULAS_PATH=/home/jaimer/Media/Peliculas
```

---

## 7. Pasos de implementacion ordenados

### Fase 1: Setup basico (ya hecho)
- [x] Instalar PHP, Composer
- [x] Instalar Laravel con Livewire
- [x] Configurar SQLite
- [x] Crear usuario inicial

### Fase 2: Configuracion
- [ ] Crear `config/media.php` con rutas de media
- [ ] Anadir variables de entorno en `.env`
- [ ] Crear rutas en `web.php`

### Fase 3: Escaneo de carpetas
- [ ] Crear `ScannerService` con metodo `scan()`
- [ ] Crear componente Livewire `ScanPage`
- [ ] Crear vista `scan-page.blade.php`
- [ ] Probar escaneo real de carpetas
- [ ] Implementar `NamingService` para parsear nombres

### Fase 4: Integracion TMDB
- [ ] Registrar API Key en TMDB
- [ ] Crear `TmdbService` con busqueda y cache
- [ ] Crear `CompareService` para cruzar datos
- [ ] Crear componente `SeriesPage` y `SerieDetailPage`
- [ ] Mostrar series con episodios faltantes

### Fase 5: Descargas
- [ ] Crear `NyaaService` para buscar torrents
- [ ] Crear `QbittorrentService` para enviar a qBittorrent
- [ ] Crear componente `DownloadsPage`
- [ ] Mostrar cola de descargas con estado

### Fase 6: Pulir
- [ ] Manejo de errores (TMDB no responde, qBittorrent apagado)
- [ ] Loading states con `wire:loading`
- [ ] Configuracion desde la interfaz
- [ ] Feedback visual de progreso

---

## 8. Cosas a tener en cuenta

### Seguridad
- No hardcodear API keys, usar `.env`
- La app corre local, pero no exponer el puerto 8000 a internet

### Rendimiento
- Cachear respuestas de TMDB en JSON (evitar rate limits)
- Escanear carpetas solo bajo demanda, no al cargar cada pagina
- Livewire actualiza solo la parte de la pagina que cambia (no recarga entera)

### UX con Livewire
- Usar `wire:loading` para mostrar spinners
- Usar `wire:confirm` para acciones destructivas
- Usar `wire:model.live` para busquedas en tiempo real
- Los componentes Livewire se actualizan sin recargar la pagina

### Testing
- Probar `ScannerService` con carpetas reales antes de integrar
- Probar `TmdbService` con series conocidas para verificar parseo
- Probar `QbittorrentService` con un torrent pequeño primero

---

## 9. Recursos utiles

- **Laravel 13 docs**: https://laravel.com/docs/13.x
- **Livewire docs**: https://livewire.laravel.com/docs
- **Flux UI**: https://fluxui.dev
- **TMDB API**: https://developer.themoviedb.org/docs
- **qBittorrent API**: https://github.com/qbittorrent/qBittorrent/wiki/WebUI-API-(qBittorrent-4.1)
- **nyaa.si**: https://nyaa.si
- **Laracasts** (tutoriales Laravel): https://laracasts.com
- **Laravel with Livewire**: https://laravel.com/docs/13.x/livewire
