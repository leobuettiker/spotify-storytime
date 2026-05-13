# Spotify Storytime

A tiny PHP/JavaScript web app that lets you find Spotify playlists, inspect their songs, and start or stop playback from a simple storytime-friendly UI.

## MVP scope

- Spotify OAuth login
- Search the current user's playlists
- Show a playlist with its tracks
- Start a selected playlist on the current active Spotify device
- Stop/pause playback
- Plain PHP, JavaScript and CSS
- No PHP framework and no Composer dependency

## Requirements

- PHP 8.1+
- HTTPS in production
- Spotify Premium for playback control
- A Spotify Developer app

The Spotify Web API playback endpoints require `user-modify-playback-state` and only work for Spotify Premium users. The app uses Spotify as the actual player; this project is only a small remote control and playlist browser.

## Spotify app setup

Create an app in the Spotify Developer Dashboard and add the callback URL that points to this app, for example:

```text
https://example.com/callback.php
```

## Environment variables

Set these variables on the server:

```bash
SPOTIFY_CLIENT_ID=your-client-id
SPOTIFY_CLIENT_SECRET=your-client-secret
SPOTIFY_REDIRECT_URI=https://example.com/callback.php
APP_BASE_URL=https://example.com
```

For local development you can use PHP's built-in server:

```bash
SPOTIFY_CLIENT_ID=your-client-id \
SPOTIFY_CLIENT_SECRET=your-client-secret \
SPOTIFY_REDIRECT_URI=http://localhost:8080/callback.php \
APP_BASE_URL=http://localhost:8080 \
php -S localhost:8080
```

Make sure `http://localhost:8080/callback.php` is also registered as redirect URI in your Spotify Developer app.

## Deployment

Upload all files to a PHP-capable web host and configure the environment variables in the hosting control panel or web server configuration.

For Metanet/Plesk-style hosting, the important point is that the document root points to the repository root or the deployed copy of this repository.

## Notes

- Do not commit Spotify client secrets.
- Autoplay should be disabled in Spotify if playback should stop after a story playlist ends.
- Playback starts on the currently active Spotify device. Open Spotify once on the target device if Spotify reports that no active device is available.
