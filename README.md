# Media Manager

> **Important note:** This application was developed entirely by the AI assistant **OpenCode** (powered by kimi-k2.6). All code, architecture, tests, and documentation were generated through conversational iterations between the user and the AI, without traditional professional developer intervention.

---

## What is it?

**Media Manager** is a web application for managing anime (and movie) libraries. It is specifically designed for the anime ecosystem because it integrates directly with **nyaa.si** — the most popular anime torrent tracker — allowing you to search, download, and organize episodes automatically.

The app scans your local directories, queries **The Movie Database (TMDB)** for episode metadata, compares what you have against what exists, and visually shows you which episodes you are missing and which are already downloaded. All from a modern web interface built with **Livewire + Flux UI**.

---

## Key Features

- **Automatic library scanning** — Detects video files (`mkv`, `mp4`, `avi`, etc.) in your Animes and Movies folders.
- **TMDB metadata** — Fetches episode information (name, air date, season) from The Movie Database.
- **Missing episode detection** — Cross-references your local files against TMDB's episode list to show what you still need to download.
- **Integrated nyaa.si search** — Search torrents directly from the app, filtering by fansubber (e.g. Erai-raws) and quality (1080p).
- **qBittorrent download integration** — Send magnet links directly to your qBittorrent instance with one click.
- **Automatic renaming** — When a torrent finishes downloading, qBittorrent automatically runs a Python script that renames files to the standard `SeriesNameSXXEXX.ext` format.
- **Smart caching** — TMDB and nyaa data is cached locally to avoid repeated API calls.
- **Automated tests** — 57+ PHPUnit tests covering all major services and components.

---

## Technologies

| Layer | Technology |
|------|-----------|
| Backend | Laravel 13 (PHP 8.3+) |
| Frontend | Livewire v4 + Flux UI v2 + Tailwind CSS v4 |
| Authentication | Laravel Fortify |
| Database | SQLite (default) / MariaDB / MySQL |
| Downloads | qBittorrent Web API |
| Torrent search | nyaa.si RSS |
| Metadata | TMDB API v3 |
| Renaming | Python 3 (script included in repo) |

---

## Requirements

- PHP 8.3 or higher
- Composer
- Node.js + npm
- qBittorrent with Web UI enabled (or qbittorrent-nox)
- Python 3 (for the automatic renaming script)
- (Optional) MariaDB/MySQL — SQLite is used by default

---

## Installation

### 1. Clone the repository

```bash
git clone <repo-url>
cd media-manager
```

### 2. Install dependencies

```bash
composer install
npm install
npm run build
```

### 3. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit the `.env` file with your values. The **Media Manager — Required Variables** section is mandatory. See the configuration section below for details.

### 4. Create the database

If using SQLite (default):

```bash
touch database/database.sqlite
php artisan migrate
```

If using MariaDB/MySQL, create the database manually and configure `DB_*` in your `.env`.

### 5. Start the server

```bash
php artisan serve
```

Open [http://localhost:8000](http://localhost:8000) in your browser.

---

## `.env` Configuration

These are the Media Manager-specific variables you must configure:

```dotenv
# TMDB API — Required for episode metadata
# Register for free at https://www.themoviedb.org/settings/api
TMDB_API_KEY=your_tmdb_api_key_here
TMDB_LANGUAGE=es-ES          # Language for episode names

# Paths to your local media library
MEDIA_ANIMES_PATH=/home/user/Media/Animes
MEDIA_PELICULAS_PATH=/home/user/Media/Peliculas

# qBittorrent Web UI
QBITTORRENT_URL=http://localhost:8080
QBITTORRENT_USER=admin
QBITTORRENT_PASS=adminadmin

# nyaa.si torrent search preferences
NYAA_BASE_URL=https://nyaa.si
NYAA_DEFAULT_SUBMITTER=Erai-raws    # Preferred fansubber
NYAA_DEFAULT_QUALITY=1080p          # Default quality
NYAA_CONCURRENCY=5                  # Parallel requests
NYAA_CACHE_TTL=86400                # Cache in seconds (24h)
```

**Note:** If you use qBittorrent Web UI on a non-standard port (e.g. `23552`), adjust `QBITTORRENT_URL` accordingly.

---

## qBittorrent Setup

The app interacts with qBittorrent through its **Web UI API**. You need to enable it:

### qBittorrent desktop
1. Open qBittorrent → Options → Web UI
2. Enable **Web User Interface**
3. Set username/password
4. Make sure the port matches `QBITTORRENT_URL` in your `.env`

### qbittorrent-nox (headless)

If you prefer the daemon version (no GUI), create a user systemd service:

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

### Automatic renaming on download completion

In qBittorrent settings, enable **Run external program on torrent completion** and set:

```
php /absolute/path/to/project/artisan media:rename "%D"
```

This will run the renaming script automatically when a torrent finishes downloading.

---

## Usage

1. **Scan your library** — Go to the main page and click *Rescan* to detect your local series.
2. **Select a series** — Your detected anime will appear. Click one to view details.
3. **Lookup TMDB** — On the detail page, click *Lookup on TMDB* to fetch episode metadata.
4. **Search torrents** — For episodes marked as "missing", click *Search nyaa* to search torrents directly.
5. **Download** — Click *Download* on a nyaa result to send the magnet to qBittorrent.
6. **Auto-rename** — When the torrent finishes, qBittorrent will run the renaming script automatically. You can also force manual renaming from the series page with the *Rename Files* button.

---

## Tests

The project includes PHPUnit tests covering all major services:

```bash
# Run all tests
php artisan test --compact

# Run tests for a specific file
php artisan test --compact tests/Feature/SerieDetailPageTest.php
```

---

## Project Structure

```
media-manager/
├── app/
│   ├── Console/Commands/         # Artisan commands (media:rename)
│   ├── Livewire/                 # Livewire components (UI)
│   ├── Services/                 # Business logic
│   │   ├── CompareService.php    # Local vs TMDB cross-reference
│   │   ├── NamingService.php     # Filename parsing
│   │   ├── NyaaService.php       # nyaa.si search
│   │   ├── QbittorrentService.php # qBittorrent Web API client
│   │   ├── RenamerService.php    # Python script wrapper
│   │   ├── ScannerService.php    # Directory scanning
│   │   └── TmdbService.php       # TMDB API client
│   └── ...
├── config/media.php              # App configuration
├── resources/views/              # Blade + Livewire views
├── tests/Feature/                # Feature tests
└── .env.example                  # Example environment variables
```

---

## License

MIT

---

> **Disclaimer:** As stated at the beginning, this application was built entirely through interaction with an AI assistant. The code has been tested with automated tests, but like any software, it may contain bugs. Use it at your own risk.
