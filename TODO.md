# TODO - Media Manager

## Pendiente

- [ ] Configurar qBittorrent-nox como servicio systemd (daemon)
  - Instalar `qbittorrent-nox`
  - Crear servicio en `/etc/systemd/system/qbittorrent.service`
  - Activar e iniciar con `systemctl enable --now qbittorrent`
  - Beneficio: arranca al inicio, corre en segundo interfaz, WebUI para gestionar descargas

## Completado

- [x] Configurar qBittorrent WebUI (puerto 23552, usuario admin)
- [x] Configurar config/media.php con rutas de media
- [x] Configurar .env con TMDB_API_KEY, QBITTORRENT_*, MEDIA_*
- [x] Configurar rutas en web.php
- [x] Crear ScannerService (escaneo de carpetas)
- [x] Crear NamingService (parseo de nombres de archivos)
- [x] Crear componente Livewire ScanPage + vista
- [x] Crear componentes stub (SeriesPage, SerieDetailPage, DownloadsPage, SettingsPage)
- [x] Verificar escaneo: 5 series, 103 archivos detectados
