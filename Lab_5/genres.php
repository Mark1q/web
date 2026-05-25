<?php
$pageTitle = 'Genres';
require_once 'header.php';
?>

<h1 class="page-title">Genres</h1>
<p class="page-subtitle">Manage your book categories</p>

<div id="alerts"></div>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:2rem;align-items:start;flex-wrap:wrap" class="genres-layout">

  <div class="card">
    <h2 style="font-family:'Playfair Display',serif;font-size:1.2rem;margin-bottom:1.25rem;color:var(--ink)">Add Genre</h2>
    <form id="add-genre-form" novalidate>
      <div class="form-group" style="margin-bottom:1rem">
        <label>Genre Name *</label>
        <input type="text" id="g-name" required placeholder="e.g. Magical Realism">
      </div>
      <button type="button" class="btn btn-primary" id="add-genre-btn" style="width:100%">+ Add Genre</button>
    </form>
  </div>

  <div>
    <div class="filter-bar" style="margin-bottom:1rem">
      <label>Search</label>
      <div class="search-box" style="flex:1">
        <span class="search-icon">🔍</span>
        <input type="text" id="genre-search" placeholder="Filter genres…">
      </div>
    </div>
    <div id="genre-list">
      <div class="loading-wrap"><div class="spinner"></div> Loading…</div>
    </div>
  </div>
</div>

<div class="modal-overlay" id="edit-genre-modal">
  <div class="modal" style="max-width:400px">
    <div class="modal-header">
      <span class="modal-title">✏️ Edit Genre</span>
      <button class="modal-close">✕</button>
    </div>
    <div class="modal-body">
      <form id="edit-genre-form" novalidate>
        <input type="hidden" id="eg-id">
        <div class="form-group">
          <label>Genre Name *</label>
          <input type="text" id="eg-name" required>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost modal-close">Cancel</button>
      <button class="btn btn-primary" id="save-genre-btn">💾 Save</button>
    </div>
  </div>
</div>

<script>
let genres = [];

async function loadGenres() {
  const data = await ajax('api/genres.php');
  genres = data.genres || [];
  
  const q = document.getElementById('genre-search').value.toLowerCase();
  renderGenres(q ? genres.filter(g => g.name.toLowerCase().includes(q)) : genres);
}

function renderGenres(list) {
  const c = document.getElementById('genre-list');
  if (!list.length) {
    c.innerHTML = '<div class="empty-state"><div class="empty-icon">🏷️</div><p>No genres yet.</p></div>';
    return;
  }
  const rows = list.map(g => `
    <tr class="genre-row">
      <td>
        <div style="font-weight:600;font-family:'Playfair Display',serif">${esc(g.name)}</div>
      </td>
      <td>
        <span class="genre-tag">${g.book_count} book${g.book_count != 1 ? 's' : ''}</span>
      </td>
      <td class="td-actions">
        <button class="btn btn-primary btn-sm" onclick="openEditGenre(${g.id})">✏️ Edit</button>
        <button class="btn btn-danger btn-sm" onclick="deleteGenre(${g.id}, '${esc(g.name).replace(/'/g,"\\'")}')">🗑</button>
      </td>
    </tr>`).join('');

  c.innerHTML = `
    <div class="table-wrap">
      <table>
        <thead><tr><th>Name</th><th>Books</th><th>Actions</th></tr></thead>
        <tbody>${rows}</tbody>
      </table>
    </div>`;
}

function esc(s) { return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

let lastFilter = '';

document.getElementById('genre-search').addEventListener('input', function() {
  const q = this.value.toLowerCase();
  
  if (q !== lastFilter) {
    previousFilter = lastFilter;  // save the old one 
    lastFilter = q;
    
    if (previousFilter) {
      document.getElementById('last-filter-label').textContent = 'Last filter: ' + previousFilter;
      document.getElementById('last-filter-label').style.display = 'block';
    }
  }

  const filtered = genres.filter(g => g.name.toLowerCase().includes(q));
  renderGenres(filtered);
});

document.getElementById('add-genre-btn').onclick = async () => {
  const form = document.getElementById('add-genre-form');
  if (!validateForm(form)) return;
  const name = document.getElementById('g-name').value.trim();
  const res  = await ajax('api/genres.php', 'POST', { name });
  if (res.success) {
    showAlert(document.getElementById('alerts'), `Genre "${name}" added!`);
    document.getElementById('g-name').value = '';
    loadGenres();
  } else {
    showAlert(document.getElementById('alerts'), res.error || 'Failed.', 'error');
  }
};

function openEditGenre(id) {
  const g = genres.find(g => g.id == id);
  if (!g) return;
  document.getElementById('eg-id').value   = g.id;
  document.getElementById('eg-name').value = g.name;
  openModal('edit-genre-modal');
}

document.getElementById('save-genre-btn').onclick = async () => {
  const form = document.getElementById('edit-genre-form');
  if (!validateForm(form)) return;
  const res = await ajax('api/genres.php', 'PUT', {
    id:   parseInt(document.getElementById('eg-id').value),
    name: document.getElementById('eg-name').value.trim(),
  });
  if (res.success) {
    closeModal('edit-genre-modal');
    showAlert(document.getElementById('alerts'), 'Genre updated!');
    loadGenres();
  } else {
    showAlert(document.getElementById('alerts'), res.error || 'Update failed.', 'error');
  }
};

async function deleteGenre(id, name) {
  const ok = await confirmAction('Delete Genre', `Delete the genre "${name}"?`);
  if (!ok) return;
  const res = await ajax('api/genres.php?id=' + id, 'DELETE');
  if (res.success) {
    showAlert(document.getElementById('alerts'), 'Genre deleted.');
    loadGenres();
  } else {
    showAlert(document.getElementById('alerts'), res.error || 'Delete failed.', 'error');
  }
}

loadGenres();
</script>

<style>
@media (max-width: 640px) {
  .genres-layout { grid-template-columns: 1fr !important; }
}
</style>

<?php require_once 'footer.php'; ?>
