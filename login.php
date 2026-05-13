<?php

declare(strict_types=1);

require_once __DIR__ . '/spotify.php';

header('Location: ' . spotify_authorize_url(), true, 302);
exit;
