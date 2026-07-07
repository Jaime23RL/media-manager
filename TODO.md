# TODO - Media Manager

## Pending

- [ ] Configure qBittorrent-nox as systemd service (daemon)
  - Install `qbittorrent-nox`
  - Create service at `/etc/systemd/system/qbittorrent.service`
  - Enable and start with `systemctl enable --now qbittorrent`
  - Benefit: starts on boot, runs in background, WebUI for managing downloads

## Completed

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
