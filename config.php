<?php

declare(strict_types=1);

function app_env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }

    return $value;
}

function require_env(string $key): string
{
    $value = app_env($key);
    if ($value === null) {
        http_response_code(500);
        echo 'Missing required environment variable: ' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
        exit;
    }

    return $value;
}

function spotify_client_id(): string
{
    return require_env('SPOTIFY_CLIENT_ID');
}

function spotify_client_secret(): string
{
    return require_env('SPOTIFY_CLIENT_SECRET');
}

function spotify_redirect_uri(): string
{
    return require_env('SPOTIFY_REDIRECT_URI');
}

function app_base_url(): string
{
    return rtrim(require_env('APP_BASE_URL'), '/');
}

function start_app_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        ]);
        session_start();
    }
}
