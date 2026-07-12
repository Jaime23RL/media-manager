# Media Manager

> **Nota importante:** Esta aplicación ha sido desarrollada completamente con el asistente de inteligencia artificial **OpenCode** (powered by kimi-k2.6). Todo el código, arquitectura, tests y documentación fueron generados mediante iteraciones conversacionales entre el usuario y la IA, sin intervención manual de desarrollo profesional tradicional.

---

## ¿Qué es?

**Media Manager** es una aplicación web para gestionar bibliotecas de anime (y películas). Está diseñada específicamente para el ecosistema de anime porque integra directamente con **nyaa.si** — el tracker de torrents más popular para contenido anime — permitiendo buscar, descargar y organizar capítulos automáticamente.

La app escanea tus directorios locales, consulta **The Movie Database (TMDB)** para obtener metadatos de episodios, compara lo que tienes contra lo que existe, y te muestra visualmente qué capítulos te faltan y cuáles ya están descargados. Todo desde una interfaz web moderna construida con **Livewire + Flux UI**.

---

## Características principales

- **Escaneo automático de bibliotecas** — Detecta archivos de video (`mkv`, `mp4`, `avi`, etc.) en tus carpetas de Animes y Películas.
- **Metadatos de TMDB** — Obtiene información de episodios (nombre, fecha de emisión, temporada) desde The Movie Database.
- **Detección de capítulos faltantes** — Cruza tus archivos locales con la lista de episodios de TMDB para mostrarte qué te falta descargar.
- **Búsqueda integrada en nyaa.si** — Busca torrents directamente desde la app, filtrando por subtitulador (ej. Erai-raws) y calidad (1080p).
- **Descarga con qBittorrent** — Envía magnet links directamente a tu instancia de qBittorrent con un solo clic.
- **Renombrado automático** — Cuando un torrent termina de descargarse, qBittorrent ejecuta automáticamente un script Python que renombra los archivos al formato estándar `SerieSXXEXX.ext`.
- **Caché inteligente** — Los datos de TMDB y nyaa se cachean localmente para evitar llamadas repetidas a APIs externas.
- **Tests automatizados** — 57+ tests de PHPUnit cubriendo todos los servicios y componentes principales.

---

## Tecnologías

| Capa | Tecnología |
|------|-----------|
| Backend | Laravel 13 (PHP 8.3+) |
| Frontend | Livewire v4 + Flux UI v2 + Tailwind CSS v4 |
| Autenticación | Laravel Fortify |
| Base de datos | SQLite (por defecto) / MariaDB / MySQL |
| Descargas | qBittorrent Web API |
| Búsqueda torrents | nyaa.si RSS |
| Metadatos | TMDB API v3 |
| Renombrado | Python 3 (script incluido en el repo) |

---

## Requisitos

- PHP 8.3 o superior
- Composer
- Node.js + npm
- qBittorrent con Web UI habilitada (o qbittorrent-nox)
- Python 3 (para el script de renombrado automático)
- (Opcional) MariaDB/MySQL — por defecto usa SQLite

---

## Instalación

### 1. Clonar el repositorio

```bash
git clone <url-del-repo>
cd media-manager
```

### 2. Instalar dependencias

```bash
composer install
npm install
npm run build
```

### 3. Configurar el entorno

```bash
cp .env.example .env
php artisan key:generate
```

Edita el archivo `.env` con tus valores. La sección **Media Manager — Required Variables** es obligatoria. Ver la sección de configuración más abajo para detalles.

### 4. Crear la base de datos

Si usas SQLite (por defecto):

```bash
touch database/database.sqlite
php artisan migrate
```

Si usas MariaDB/MySQL, crea la base de datos manualmente y configura `DB_*` en el `.env`.

### 5. Iniciar el servidor

```bash
php artisan serve
```

Abre [http://localhost:8000](http://localhost:8000) en tu navegador.

---

## Configuración del `.env`

Estas son las variables específicas de Media Manager que debes configurar obligatoriamente:

```dotenv
# TMDB API — Obligatorio para metadatos de episodios
# Regístrate gratis en https://www.themoviedb.org/settings/api
TMDB_API_KEY=your_tmdb_api_key_here
TMDB_LANGUAGE=es-ES          # Idioma para nombres de episodios

# Rutas de tu biblioteca local
MEDIA_ANIMES_PATH=/home/user/Media/Animes
MEDIA_PELICULAS_PATH=/home/user/Media/Peliculas

# qBittorrent Web UI
QBITTORRENT_URL=http://localhost:8080
QBITTORRENT_USER=admin
QBITTORRENT_PASS=adminadmin

# Preferencias de búsqueda en nyaa.si
NYAA_BASE_URL=https://nyaa.si
NYAA_DEFAULT_SUBMITTER=Erai-raws    # Fansub preferido
NYAA_DEFAULT_QUALITY=1080p          # Calidad por defecto
NYAA_CONCURRENCY=5                  # Peticiones paralelas
NYAA_CACHE_TTL=86400                # Cache en segundos (24h)
```

**Nota:** Si usas qBittorrent Web UI en un puerto no estándar (por ejemplo `23552`), ajusta `QBITTORRENT_URL` acorde.

---

## Configuración de qBittorrent

La app interactúa con qBittorrent a través de su **Web UI API**. Necesitas habilitarla:

### qBittorrent desktop
1. Abre qBittorrent → Opciones → Web UI
2. Activa **Web User Interface**
3. Establece usuario/contraseña
4. Asegúrate de que el puerto coincida con `QBITTORRENT_URL` en tu `.env`

### qbittorrent-nox (headless)

Si prefieres la versión daemon (sin GUI), crea un servicio systemd de usuario:

```ini
# ~/.config/systemd/user/qbittorrent.service
[Unit]
Description=qBittorrent-nox daemon
After=network.target

[Service]
Type=simple
ExecStart=/usr/bin/qbittorrent-nox --webui-port=8080
Restart=on-failure

[Install]
WantedBy=default.target
```

```bash
systemctl --user daemon-reload
systemctl --user enable --now qbittorrent.service
```

### Renombrado automático al completar descarga

En la configuración de qBittorrent, activa **Run external program on torrent completion** y establece:

```
php /ruta/absoluta/al/proyecto/artisan media:rename "%D"
```

Esto ejecutará el script de renombrado automáticamente cuando un torrent termine de descargarse.

---

## Uso

1. **Escanea tu biblioteca** — Ve a la pantalla principal y haz clic en *Rescan* para detectar tus series locales.
2. **Selecciona una serie** — Aparecerán tus animes detectados. Haz clic en uno para ver los detalles.
3. **Lookup TMDB** — En la página de detalle, haz clic en *Lookup on TMDB* para obtener los metadatos de episodios.
4. **Busca torrents** — Para capítulos marcados como "missing", haz clic en *Search nyaa* para buscar torrents directamente.
5. **Descarga** — Haz clic en *Download* en un resultado de nyaa para enviar el magnet a qBittorrent.
6. **Renombra automáticamente** — Cuando el torrent termine, qBittorrent ejecutará el script de renombrado. También puedes forzar el renombrado manual desde la página de la serie con el botón *Rename Files*.

---

## Tests

El proyecto incluye tests de PHPUnit que cubren todos los servicios principales:

```bash
# Ejecutar todos los tests
php artisan test --compact

# Ejecutar tests de un archivo específico
php artisan test --compact tests/Feature/SerieDetailPageTest.php
```

---

## Estructura del proyecto

```
media-manager/
├── app/
│   ├── Console/Commands/         # Comandos Artisan (media:rename)
│   ├── Livewire/                 # Componentes Livewire (UI)
│   ├── Services/                 # Lógica de negocio
│   │   ├── CompareService.php    # Cruce local vs TMDB
│   │   ├── NamingService.php     # Parseo de nombres de archivos
│   │   ├── NyaaService.php       # Búsqueda en nyaa.si
│   │   ├── QbittorrentService.php # Cliente Web API de qBittorrent
│   │   ├── RenamerService.php    # Wrapper del script Python
│   │   ├── ScannerService.php    # Escaneo de directorios
│   │   └── TmdbService.php       # Cliente TMDB API
│   └── ...
├── config/media.php              # Configuración de la app
├── resources/views/              # Vistas Blade + Livewire
├── tests/Feature/                # Tests de funcionalidad
└── .env.example                  # Variables de entorno de ejemplo
```

---

## Licencia

MIT

---

> **Disclaimer:** Como se indica al inicio, esta aplicación fue construida íntegramente mediante interacción con un asistente de IA. El código ha sido probado con tests automatizados, pero al igual que cualquier software, puede contener errores. Úsala bajo tu propia responsabilidad.
