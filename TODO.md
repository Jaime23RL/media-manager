# TODO - Media Manager

## Pending

- [ ] Configure qBittorrent-nox as systemd service (daemon)
  - Install `qbittorrent-nox`
  - Create service at `/etc/systemd/system/qbittorrent.service`
  - Enable and start with `systemctl enable --now qbittorrent`
  - Benefit: starts on boot, runs in background, WebUI for managing downloads

## Completed

- [x] Install Laravel Boost for AI guidelines (Livewire, Flux, Tailwind best practices)
- [x] Configure qBittorrent WebUI (port 23552, user admin)
- [x] Configure config/media.php with media paths
- [x] Configure .env with TMDB_API_KEY, QBITTORRENT_*, MEDIA_*
- [x] Configure routes in web.php
- [x] Create ScannerService (folder scanning)
- [x] Create NamingService (filename parsing)
- [x] Create Livewire ScanPage component + view
- [x] Create stub components (SeriesPage, SerieDetailPage, DownloadsPage, SettingsPage)
- [x] Verify scan: 5 series, 103 files detected
- [x] Persist scan results to JSON (storage/app/cache/local/)
- [x] Translate all comments and UI to English
- [x] Create TmdbService (search, get details, get seasons, cache)
- [x] Create CompareService (local vs TMDB comparison)
- [x] Implement SeriesPage with TMDB lookup and episode status
- [x] Implement SerieDetailPage with episodes by season
- [x] Accordion UI for episodes grouped by season
- [x] TMDB lookup results persisted to cache
- [x] Filter future episodes as 'upcoming' (no false warnings)
- [x] Local files grouped by season before TMDB lookup
- [x] SeriesPage lookup saves per-series cache for instant detail view
- [x] TMDB state persisted in animes.json (permanent across reloads)
- [x] UI padding improved in series list
- [x] Create season folders from SerieDetailPage (missing seasons)
- [x] Add Anime page - search TMDB and create folder structure
