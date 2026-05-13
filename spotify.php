<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function spotify_authorize_url(): string
{
    start_app_session();

    $state = bin2hex(random_bytes(16));
    $_SESSION['spotify_oauth_state'] = $state;

    $params = [
        'client_id' => spotify_client_id(),
        'response_type' => 'code',
        'redirect_uri' => spotify_redirect_uri(),
        'scope' => implode(' ', [
            'playlist-read-private',
            'playlist-read-collaborative',
            'user-read-playback-state',
            'user-modify-playback-state',
        ]),
        'state' => $state,
    ];

    return 'https://accounts.spotify.com/authorize?' . http_build_query($params);
}

function spotify_exchange_code(string $code): void
{
    $response = spotify_token_request([
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => spotify_redirect_uri(),
    ]);

    spotify_store_token_response($response);
}

function spotify_refresh_token(): void
{
    start_app_session();

    if (empty($_SESSION['spotify_refresh_token'])) {
        throw new RuntimeException('No refresh token available.');
    }

    $response = spotify_token_request([
        'grant_type' => 'refresh_token',
        'refresh_token' => $_SESSION['spotify_refresh_token'],
    ]);

    if (empty($response['refresh_token'])) {
        $response['refresh_token'] = $_SESSION['spotify_refresh_token'];
    }

    spotify_store_token_response($response);
}

function spotify_token_request(array $form): array
{
    $ch = curl_init('https://accounts.spotify.com/api/token');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($form),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Basic ' . base64_encode(spotify_client_id() . ':' . spotify_client_secret()),
            'Content-Type: application/x-www-form-urlencoded',
        ],
    ]);

    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        throw new RuntimeException('Spotify token request failed: ' . $error);
    }

    $json = json_decode($body, true);
    if ($status >= 400 || !is_array($json)) {
        throw new RuntimeException('Spotify token request failed with HTTP ' . $status . ': ' . $body);
    }

    return $json;
}

function spotify_store_token_response(array $response): void
{
    start_app_session();

    $_SESSION['spotify_access_token'] = $response['access_token'];
    $_SESSION['spotify_refresh_token'] = $response['refresh_token'] ?? ($_SESSION['spotify_refresh_token'] ?? null);
    $_SESSION['spotify_token_expires_at'] = time() + (int) ($response['expires_in'] ?? 3600) - 60;
}

function spotify_is_logged_in(): bool
{
    start_app_session();
    return !empty($_SESSION['spotify_access_token']);
}

function spotify_access_token(): string
{
    start_app_session();

    if (empty($_SESSION['spotify_access_token'])) {
        throw new RuntimeException('Not logged in.');
    }

    if (time() >= (int) ($_SESSION['spotify_token_expires_at'] ?? 0)) {
        spotify_refresh_token();
    }

    return (string) $_SESSION['spotify_access_token'];
}

function spotify_api(string $method, string $path, ?array $payload = null, array $query = []): array
{
    $url = 'https://api.spotify.com/v1' . $path;
    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }

    $ch = curl_init($url);
    $headers = ['Authorization: Bearer ' . spotify_access_token()];

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
    ]);

    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        return ['ok' => false, 'status' => 0, 'error' => $error];
    }

    $json = $body !== '' ? json_decode($body, true) : null;

    if ($status >= 400) {
        return [
            'ok' => false,
            'status' => $status,
            'error' => $json['error']['message'] ?? $body,
            'raw' => $json,
        ];
    }

    return [
        'ok' => true,
        'status' => $status,
        'data' => $json,
    ];
}
