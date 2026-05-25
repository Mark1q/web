<?php
$pageTitle = 'Manage Books';
require_once 'header.php';
require_once 'db.php';
$genres = [];
$res = $conn->query("SELECT * FROM genres ORDER BY name");
while ($row = $res->fetch_assoc()) $genres[] = $row;
$conn->close();
?>

<h1 class="page-title">Manage Books</h1>
<p class="page-subtitle">Edit or remove books from your library</p>

<div id="alerts"></div>

<div class="filter-bar" style="margin-bottom:1rem">
  <label>Search</label>
  <div class="search-box">
    <span class="search-icon">🔍</span>
    <input type="text" id="search-input" placeholder="Search by title, author…">
  </div>
  <label>Genre</label>
  <select id="genre-filter">
    <option value="">All Genres</option>
    <?php foreach ($genres as $g): ?>
      <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <a href="add.php" class="btn btn-primary btn-sm">+ Add Book</a>
</div>

<div id="table-container">
  <div class="loading-wrap"><div class="spinner"></div> Loading…</div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="edit-modal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">✏️ Edit Book</span>
      <button class="modal-close">✕</button>
    </div>
    <div class="modal-body">
      <form id="edit-form" class="form-grid" novalidate>
        <input type="hidden" id="e-id">
        <div class="form-group"><label>Title *</label><input type="text" id="e-title" required></div>
        <div class="form-group"><label>Author *</label><input type="text" id="e-author" required></div>
        <div class="form-group">
          <label>Genre</label>
          <select id="e-genre">
            <option value="">— None —</option>
            <?php foreach ($genres as $g): ?>
              <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label>Pages</label><input type="number" id="e-pages" data-type="int" min="1"></div>
        <div class="form-group"><label>Published Year</label><input type="number" id="e-year" data-type="year"></div>
        <div class="form-group"><label>ISBN</label><input type="text" id="e-isbn"></div>
        <div class="form-group full">
          <label>Cover Color</label>
          <div class="color-row"><input type="color" id="e-color"><span id="e-preview" style="padding:0.4rem 1rem;border-radius:3px;color:#fff;font-family:'Playfair Display',serif;font-size:0.8rem">Preview</span></div>
        </div>
        <div class="form-group full"><label>Description</label><textarea id="e-desc"></textarea></div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost modal-close">Cancel</button>
      <button class="btn btn-primary" id="save-edit-btn">💾 Save Changes</button>
    </div>
  </div>
</div>

<script>
let books = [];

async function loadBooks() {
  const genreId = document.getElementById('genre-filter').value;
  const search  = document.getElementById('search-input').value.trim();
  const params  = new URLSearchParams();
  if (genreId) params.set('genre_id', genreId);
  if (search)  params.set('search', search);

  document.getElementById('table-container').innerHTML = '<div class="loading-wrap"><div class="spinner"></div> Loading…</div>';
  const data = await ajax('api/books.php?' + params.toString());
  books = data.books || [];
  renderTable(books);
}

function renderTable(books) {
  const tc = document.getElementById('table-container');
  if (!books.length) {
    tc.innerHTML = '<div class="empty-state"><div class="empty-icon">📭</div><p>No books found.</p></div>';
    return;
  }
  let rows = books.map(b => `
    <tr>
      <td>
        <div style="display:flex;align-items:center;gap:0.75rem">
          <div style="width:28px;height:40px;background:${b.cover_color};border-radius:2px;flex-shrink:0"></div>
          <div>
            <div style="font-weight:600;font-family:'Playfair Display',serif">${esc(b.title)}</div>
            <div style="font-size:0.82rem;color:var(--muted);font-style:italic">${esc(b.author)}</div>
          </div>
        </div>
      </td>
      <td><span class="genre-tag">${esc(b.genre_name || '—')}</span></td>
      <td>${b.pages || '—'}</td>
      <td>${b.published_year || '—'}</td>
      <td>${b.is_lent ? '<span class="book-badge lent">Lent</span>' : '<span class="book-badge" style="background:var(--forest)">In shelf</span>'}</td>
      <td class="td-actions">
        <button class="btn btn-primary btn-sm" onclick="openEdit(${b.id})">✏️ Edit</button>
        <button class="btn btn-danger btn-sm" onclick="deleteBook(${b.id}, '${esc(b.title).replace(/'/g,"\\'")}')">🗑</button>
      </td>
    </tr>`).join('');

  tc.innerHTML = `
    <div class="table-wrap">
      <table>
        <thead><tr><th>Book</th><th>Genre</th><th>Pages</th><th>Year</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>${rows}</tbody>
      </table>
    </div>`;
}

function esc(s) { return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function openEdit(id) {
  const book = books.find(b => b.id === id);
  if (!book) return;
  document.getElementById('e-id').value    = book.id;
  document.getElementById('e-title').value = book.title;
  document.getElementById('e-author').value= book.author;
  document.getElementById('e-genre').value = book.genre_id || '';
  document.getElementById('e-pages').value = book.pages || '';
  document.getElementById('e-year').value  = book.published_year || '';
  document.getElementById('e-isbn').value  = book.isbn || '';
  document.getElementById('e-color').value = book.cover_color || '#4a6fa5';
  document.getElementById('e-desc').value  = book.description || '';
  const prev = document.getElementById('e-preview');
  prev.textContent = book.title;
  prev.style.background = book.cover_color;
  openModal('edit-modal');
}

document.getElementById('e-color').addEventListener('input', e => {
  const prev = document.getElementById('e-preview');
  prev.style.background = e.target.value;
});

document.getElementById('save-edit-btn').onclick = async () => {
  const form = document.getElementById('edit-form');
  if (!validateForm(form)) return;
  const payload = {
    id:             parseInt(document.getElementById('e-id').value),
    title:          document.getElementById('e-title').value.trim(),
    author:         document.getElementById('e-author').value.trim(),
    genre_id:       document.getElementById('e-genre').value,
    pages:          document.getElementById('e-pages').value,
    published_year: document.getElementById('e-year').value,
    isbn:           document.getElementById('e-isbn').value.trim(),
    cover_color:    document.getElementById('e-color').value,
    description:    document.getElementById('e-desc').value.trim(),
  };
  const res = await ajax('api/books.php', 'PUT', payload);
  if (res.success) {
    closeModal('edit-modal');
    showAlert(document.getElementById('alerts'), 'Book updated!');
    loadBooks();
  } else {
    showAlert(document.getElementById('alerts'), res.error || 'Update failed.', 'error');
  }
};

async function deleteBook(id, title) {
  const ok = await confirmAction('Delete Book', `Delete "${title}"? This cannot be undone.`);
  if (!ok) return;
  const res = await ajax('api/books.php?id=' + id, 'DELETE');
  if (res.success) {
    showAlert(document.getElementById('alerts'), 'Book deleted.');
    loadBooks();
  } else {
    showAlert(document.getElementById('alerts'), res.error || 'Delete failed.', 'error');
  }
}

let timer;
document.getElementById('search-input').addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(loadBooks, 400); });
document.getElementById('genre-filter').addEventListener('change', loadBooks);

loadBooks();
</script>

<?php require_once 'footer.php'; ?>
