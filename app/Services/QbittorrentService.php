<?php

namespace App\Services;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class QbittorrentService
{
    private string $baseUrl;

    private string $username;

    private string $password;

    private CookieJar $cookieJar;

    private bool $isLoggedIn = false;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('media.qbittorrent.url'), '/');
        $this->username = config('media.qbittorrent.user');
        $this->password = config('media.qbittorrent.password');
        $this->cookieJar = new CookieJar;
    }

    /**
     * Authenticate with the qBittorrent Web API.
     */
    public function login(): bool
    {
        $response = Http::withOptions(['cookies' => $this->cookieJar])
            ->asForm()
            ->post("{$this->baseUrl}/api/v2/auth/login", [
                'username' => $this->username,
                'password' => $this->password,
            ]);

        if ($response->successful() && ($response->body() === 'Ok.' || $response->body() === '')) {
            $this->isLoggedIn = true;

            return true;
        }

        $this->isLoggedIn = false;

        return false;
    }

    /**
     * Execute an API request with automatic re-authentication on 403.
     */
    private function request(string $method, string $endpoint, array $data = []): Response
    {
        $url = "{$this->baseUrl}{$endpoint}";

        $http = Http::withOptions(['cookies' => $this->cookieJar]);

        if ($method === 'GET') {
            $response = $http->get($url, $data);
        } else {
            $response = $http->asForm()->post($url, $data);
        }

        // If unauthorized, try to login and retry once
        if ($response->status() === 403) {
            if ($this->login()) {
                $http = Http::withOptions(['cookies' => $this->cookieJar]);
                if ($method === 'GET') {
                    $response = $http->get($url, $data);
                } else {
                    $response = $http->asForm()->post($url, $data);
                }
            }
        }

        return $response;
    }

    /**
     * Add a magnet link to qBittorrent.
     */
    public function addMagnet(string $magnetLink, ?string $savePath = null): bool
    {
        $data = ['urls' => $magnetLink];

        if ($savePath !== null) {
            $data['savepath'] = $savePath;
        }

        $response = $this->request('POST', '/api/v2/torrents/add', $data);

        return $response->successful();
    }

    /**
     * Get torrents from qBittorrent with optional status filter.
     *
     * @param  string|null  $filter  One of: all, downloading, completed, paused, active, inactive, resumed, stalled, stalled_uploading, stalled_downloading, errored
     * @return array List of normalized torrent data
     */
    public function getTorrents(?string $filter = null): array
    {
        $params = [];
        if ($filter !== null && $filter !== 'all') {
            $params['filter'] = $filter;
        }

        $response = $this->request('GET', '/api/v2/torrents/info', $params);

        if (! $response->successful()) {
            return [];
        }

        $torrents = $response->json() ?? [];

        return array_map(fn (array $t) => $this->normalizeTorrent($t), $torrents);
    }

    /**
     * Pause a torrent by its hash.
     */
    public function pauseTorrent(string $hash): bool
    {
        $response = $this->request('POST', '/api/v2/torrents/pause', [
            'hashes' => $hash,
        ]);

        return $response->successful();
    }

    /**
     * Resume a torrent by its hash.
     */
    public function resumeTorrent(string $hash): bool
    {
        $response = $this->request('POST', '/api/v2/torrents/resume', [
            'hashes' => $hash,
        ]);

        return $response->successful();
    }

    /**
     * Delete a torrent by its hash.
     */
    public function deleteTorrent(string $hash, bool $deleteFiles = false): bool
    {
        $response = $this->request('POST', '/api/v2/torrents/delete', [
            'hashes' => $hash,
            'deleteFiles' => $deleteFiles ? 'true' : 'false',
        ]);

        return $response->successful();
    }

    /**
     * Check if the service is connected and authenticated.
     */
    public function isConnected(): bool
    {
        $response = $this->request('GET', '/api/v2/app/version');

        return $response->successful();
    }

    /**
     * Set qBittorrent application preferences.
     */
    public function setPreferences(array $preferences): bool
    {
        $response = $this->request('POST', '/api/v2/app/setPreferences', [
            'json' => json_encode($preferences),
        ]);

        return $response->successful();
    }

    /**
     * Normalize a raw torrent object from qBittorrent into a consistent array.
     */
    private function normalizeTorrent(array $torrent): array
    {
        return [
            'name' => $torrent['name'] ?? '',
            'hash' => $torrent['hash'] ?? '',
            'magnet_uri' => $torrent['magnet_uri'] ?? '',
            'size' => (int) ($torrent['size'] ?? 0),
            'progress' => (float) ($torrent['progress'] ?? 0),
            'dlspeed' => (int) ($torrent['dlspeed'] ?? 0),
            'upspeed' => (int) ($torrent['upspeed'] ?? 0),
            'eta' => (int) ($torrent['eta'] ?? -1),
            'state' => $torrent['state'] ?? 'unknown',
            'category' => $torrent['category'] ?? '',
            'tags' => $torrent['tags'] ?? '',
            'completed' => (int) ($torrent['completed'] ?? 0),
            'num_seeds' => (int) ($torrent['num_seeds'] ?? 0),
            'num_leechs' => (int) ($torrent['num_leechs'] ?? 0),
            'ratio' => (float) ($torrent['ratio'] ?? 0),
            'save_path' => $torrent['save_path'] ?? '',
            'added_on' => (int) ($torrent['added_on'] ?? 0),
            'completion_on' => (int) ($torrent['completion_on'] ?? 0),
        ];
    }
}
