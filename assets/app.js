const playlistList = document.querySelector('#playlist-list');
const playlistDetail = document.querySelector('#playlist-detail');
const searchInput = document.querySelector('#playlist-search');
const reloadButton = document.querySelector('#reload-playlists');
const stopButton = document.querySelector('#stop-playback');

let selectedPlaylistId = null;
let searchTimeout = null;

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function formatDuration(ms) {
  const totalSeconds = Math.round((ms || 0) / 1000);
  const minutes = Math.floor(totalSeconds / 60);
  const seconds = totalSeconds % 60;
  return `${minutes}:${String(seconds).padStart(2, '0')}`;
}

function setDetailMessage(message) {
  playlistDetail.className = 'empty-state';
  playlistDetail.innerHTML = escapeHtml(message);
}

async function api(action, options = {}) {
  const params = new URLSearchParams({ action, ...(options.query || {}) });
  const response = await fetch(`api.php?${params.toString()}`, {
    method: options.method || 'GET',
    headers: options.body ? { 'Content-Type': 'application/json' } : undefined,
    body: options.body ? JSON.stringify(options.body) : undefined,
  });

  const data = await response.json().catch(() => ({ ok: false, error: 'Invalid JSON response.' }));

  if (!response.ok || data.ok === false) {
    throw new Error(data.error || `Request failed with HTTP ${response.status}`);
  }

  return data;
}

async function loadPlaylists() {
  const q = searchInput.value.trim();
  playlistList.innerHTML = '<div class="loading">Loading playlists...</div>';

  try {
    const data = await api('playlists', { query: q ? { q } : {} });

    if (!data.items.length) {
      playlistList.innerHTML = '<div class="empty-state">No matching playlists found.</div>';
      return;
    }

    playlistList.innerHTML = data.items.map((playlist) => `
      <button class="playlist-item ${playlist.id === selectedPlaylistId ? 'active' : ''}" type="button" data-id="${escapeHtml(playlist.id)}">
        ${playlist.image ? `<img src="${escapeHtml(playlist.image)}" alt="" loading="lazy">` : '<div class="playlist-placeholder">♪</div>'}
        <span>
          <strong>${escapeHtml(playlist.name)}</strong>
          <small>${escapeHtml(playlist.tracks_total)} songs${playlist.owner ? ` · ${escapeHtml(playlist.owner)}` : ''}</small>
        </span>
      </button>
    `).join('');
  } catch (error) {
    playlistList.innerHTML = `<div class="error-box">${escapeHtml(error.message)}</div>`;
  }
}

async function loadPlaylist(id) {
  selectedPlaylistId = id;
  setDetailMessage('Loading playlist...');
  document.querySelectorAll('.playlist-item').forEach((item) => {
    item.classList.toggle('active', item.dataset.id === id);
  });

  try {
    const data = await api('playlist', { query: { id } });
    const playlist = data.playlist;
    const totalMs = playlist.tracks.reduce((sum, track) => sum + (track.duration_ms || 0), 0);

    playlistDetail.className = 'playlist-detail';
    playlistDetail.innerHTML = `
      <div class="detail-header">
        ${playlist.image ? `<img src="${escapeHtml(playlist.image)}" alt="" loading="lazy">` : '<div class="detail-placeholder">♪</div>'}
        <div>
          <p class="eyebrow">Selected playlist</p>
          <h2>${escapeHtml(playlist.name)}</h2>
          <p>${escapeHtml(playlist.tracks.length)} songs · ${formatDuration(totalMs)}</p>
          ${playlist.description ? `<p class="description">${escapeHtml(playlist.description)}</p>` : ''}
          <button class="primary-button" type="button" data-play-uri="${escapeHtml(playlist.uri)}">Play this story</button>
        </div>
      </div>
      <ol class="track-list">
        ${playlist.tracks.map((track) => `
          <li>
            <span>
              <strong>${escapeHtml(track.name)}</strong>
              <small>${escapeHtml((track.artists || []).filter(Boolean).join(', '))}</small>
            </span>
            <time>${formatDuration(track.duration_ms)}</time>
          </li>
        `).join('')}
      </ol>
    `;
  } catch (error) {
    playlistDetail.className = 'error-box';
    playlistDetail.innerHTML = escapeHtml(error.message);
  }
}

async function playPlaylist(uri) {
  try {
    await api('play', { method: 'POST', body: { playlist_uri: uri } });
    showToast('Playback started.');
  } catch (error) {
    showToast(error.message, true);
  }
}

async function stopPlayback() {
  try {
    await api('stop', { method: 'POST' });
    showToast('Playback stopped.');
  } catch (error) {
    showToast(error.message, true);
  }
}

function showToast(message, isError = false) {
  const existing = document.querySelector('.toast');
  existing?.remove();

  const toast = document.createElement('div');
  toast.className = `toast ${isError ? 'error' : ''}`;
  toast.textContent = message;
  document.body.append(toast);

  window.setTimeout(() => toast.remove(), 3500);
}

playlistList.addEventListener('click', (event) => {
  const item = event.target.closest('.playlist-item');
  if (item) {
    loadPlaylist(item.dataset.id);
  }
});

playlistDetail.addEventListener('click', (event) => {
  const button = event.target.closest('[data-play-uri]');
  if (button) {
    playPlaylist(button.dataset.playUri);
  }
});

searchInput.addEventListener('input', () => {
  window.clearTimeout(searchTimeout);
  searchTimeout = window.setTimeout(loadPlaylists, 250);
});

reloadButton.addEventListener('click', loadPlaylists);
stopButton.addEventListener('click', stopPlayback);

loadPlaylists();
