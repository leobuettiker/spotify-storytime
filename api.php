<?php

declare(strict_types=1);

require_once __DIR__ . '/spotify.php';

start_app_session();
header('Content-Type: application/json; charset=utf-8');

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!spotify_is_logged_in()) {
    json_response(['ok' => false, 'error' => 'Not logged in.'], 401);
}

$action = (string) ($_GET['action'] ?? '');

try {
    if ($action === 'playlists') {
        $query = trim((string) ($_GET['q'] ?? ''));
        $items = [];
        $urlQuery = ['limit' => 50, 'offset' => 0];

        do {
            $response = spotify_api('GET', '/me/playlists', null, $urlQuery);
            if (!$response['ok']) {
                json_response($response, $response['status'] ?: 500);
            }

            $data = $response['data'];
            foreach (($data['items'] ?? []) as $playlist) {
                if ($query !== '' && stripos((string) $playlist['name'], $query) === false) {
                    continue;
                }

                $items[] = [
                    'id' => $playlist['id'],
                    'name' => $playlist['name'],
                    'description' => strip_tags((string) ($playlist['description'] ?? '')),
                    'tracks_total' => $playlist['tracks']['total'] ?? 0,
                    'uri' => $playlist['uri'],
                    'image' => $playlist['images'][0]['url'] ?? null,
                    'owner' => $playlist['owner']['display_name'] ?? null,
                ];
            }

            $next = $data['next'] ?? null;
            $urlQuery['offset'] += 50;
        } while ($next !== null && count($items) < 200);

        json_response(['ok' => true, 'items' => $items]);
    }

    if ($action === 'playlist') {
        $playlistId = (string) ($_GET['id'] ?? '');
        if ($playlistId === '') {
            json_response(['ok' => false, 'error' => 'Missing playlist id.'], 400);
        }

        $playlistResponse = spotify_api('GET', '/playlists/' . rawurlencode($playlistId), null, [
            'fields' => 'id,name,description,uri,images,tracks(total,items(track(id,name,uri,duration_ms,artists(name))))',
            'limit' => 100,
        ]);

        if (!$playlistResponse['ok']) {
            json_response($playlistResponse, $playlistResponse['status'] ?: 500);
        }

        $playlist = $playlistResponse['data'];
        $tracks = [];
        foreach (($playlist['tracks']['items'] ?? []) as $item) {
            $track = $item['track'] ?? null;
            if (!$track || empty($track['uri'])) {
                continue;
            }

            $tracks[] = [
                'id' => $track['id'] ?? null,
                'name' => $track['name'] ?? 'Unknown track',
                'uri' => $track['uri'],
                'duration_ms' => $track['duration_ms'] ?? 0,
                'artists' => array_map(static fn(array $artist): string => $artist['name'] ?? '', $track['artists'] ?? []),
            ];
        }

        json_response([
            'ok' => true,
            'playlist' => [
                'id' => $playlist['id'],
                'name' => $playlist['name'],
                'description' => strip_tags((string) ($playlist['description'] ?? '')),
                'uri' => $playlist['uri'],
                'image' => $playlist['images'][0]['url'] ?? null,
                'tracks_total' => $playlist['tracks']['total'] ?? count($tracks),
                'tracks' => $tracks,
            ],
        ]);
    }

    if ($action === 'play') {
        $payload = json_decode(file_get_contents('php://input') ?: '{}', true);
        $playlistUri = (string) ($payload['playlist_uri'] ?? '');
        if ($playlistUri === '') {
            json_response(['ok' => false, 'error' => 'Missing playlist URI.'], 400);
        }

        $response = spotify_api('PUT', '/me/player/play', [
            'context_uri' => $playlistUri,
            'offset' => ['position' => 0],
            'position_ms' => 0,
        ]);

        json_response($response, $response['status'] ?: ($response['ok'] ? 200 : 500));
    }

    if ($action === 'stop') {
        $response = spotify_api('PUT', '/me/player/pause');
        json_response($response, $response['status'] ?: ($response['ok'] ? 200 : 500));
    }

    json_response(['ok' => false, 'error' => 'Unknown action.'], 404);
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
