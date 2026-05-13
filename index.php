<?php

declare(strict_types=1);

require_once __DIR__ . '/spotify.php';

start_app_session();
$isLoggedIn = spotify_is_logged_in();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Spotify Storytime</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="app-shell">
        <header class="hero">
            <div>
                <p class="eyebrow">Spotify Storytime</p>
                <h1>Pick one story. Start playback. Stop when needed.</h1>
                <p class="hero-text">A tiny remote for story playlists, built for simple family use.</p>
            </div>
            <div class="auth-card">
                <?php if ($isLoggedIn): ?>
                    <p class="status-pill">Connected to Spotify</p>
                    <a class="secondary-button" href="logout.php">Logout</a>
                <?php else: ?>
                    <p>Connect Spotify to search and control playback.</p>
                    <a class="primary-button" href="login.php">Login with Spotify</a>
                <?php endif; ?>
            </div>
        </header>

        <?php if ($isLoggedIn): ?>
            <section class="controls-card">
                <div class="search-row">
                    <label for="playlist-search">Search playlists</label>
                    <input id="playlist-search" type="search" placeholder="Spidey, bedtime, stories..." autocomplete="off">
                </div>
                <button id="reload-playlists" class="secondary-button" type="button">Reload</button>
                <button id="stop-playback" class="danger-button" type="button">Stop playback</button>
            </section>

            <section class="content-grid">
                <aside class="panel">
                    <h2>Playlists</h2>
                    <div id="playlist-list" class="playlist-list"></div>
                </aside>

                <section class="panel detail-panel">
                    <div id="playlist-detail" class="empty-state">
                        Select a playlist to see its songs.
                    </div>
                </section>
            </section>
        <?php else: ?>
            <section class="panel empty-state">
                Login first. After that, you can search playlists and start playback on an active Spotify device.
            </section>
        <?php endif; ?>
    </main>

    <?php if ($isLoggedIn): ?>
        <script src="assets/app.js" defer></script>
    <?php endif; ?>
</body>
</html>
