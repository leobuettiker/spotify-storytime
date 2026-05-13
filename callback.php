<?php

declare(strict_types=1);

require_once __DIR__ . '/spotify.php';

start_app_session();

if (!empty($_GET['error'])) {
    http_response_code(400);
    echo 'Spotify login failed: ' . htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8');
    exit;
}

$state = (string) ($_GET['state'] ?? '');
$expectedState = (string) ($_SESSION['spotify_oauth_state'] ?? '');

if ($state === '' || $expectedState === '' || !hash_equals($expectedState, $state)) {
    http_response_code(400);
    echo 'Invalid OAuth state.';
    exit;
}

$code = (string) ($_GET['code'] ?? '');
if ($code === '') {
    http_response_code(400);
    echo 'Missing OAuth code.';
    exit;
}

try {
    spotify_exchange_code($code);
    unset($_SESSION['spotify_oauth_state']);
    header('Location: ' . app_base_url() . '/index.php', true, 302);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Spotify login failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
