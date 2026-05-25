<?php
$pageTitle = 'Browse Books';
require_once 'header.php';
require_once 'db.php';

$genres = [];
$res = $conn->query("SELECT * FROM genres ORDER BY name");
while ($row = $res->fetch_assoc()) $genres[] = $row;
$conn->close();
?>

<h1 class="page-title">Browse Your Library</h1>
<p class="page-subtitle">Explore your collection by genre or search</p>

<div class="stats-bar" id="stats-bar">
  <div class="stat-card"><div class="stat-num" id="s-total">…</div><div class="stat-label">Total Books</div></div>
  <div class="stat-card"><div class="stat-num" id="s-lent">…</div><div class="stat-label">Lent Out</div></div>
  <div class="stat-card"><div class="stat-num" id="s-genres">…</div><div class="stat-label">Genres</div></div>
  <div class="stat-card"><div class="stat-num" id="s-overdue" style="color:var(--burgundy)">…</div><div class="stat-label">Overdue</div></div>
</div>

<div class="filter-bar">
  <label>Filter</label>
  <div class="search-box">
    <span class="search-icon">🔍</span>
    <input type="text" id="search-input" placeholder="Search title, author, ISBN…">
  </div>
  <label>Genre</label>
  <select id="genre-filter">
    <option value="">All Genres</option>
    <?php foreach ($genres as $g): ?>
      <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <span class="filter-memory" id="filter-memory" style="display:none"></span>
</div>

<div id="books-container">
  <div class="loading-wrap"><div class="spinner"></div><span>Loading your library…</span></div>
</div>

<div class="modal-overlay" id="book-modal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="modal-mode-label">Book Details</span>
      <button class="modal-close">✕</button>
    </div>
    <div class="modal-body" id="book-modal-body"></div>
    <div class="modal-footer" id="book-modal-footer"></div>
  </div>
</div>

<div class="modal-overlay" id="lend-modal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">📤 Lend This Book</span>
      <button class="modal-close">✕</button>
    </div>
    <div class="modal-body">
      <form id="lend-form" class="form-grid">
        <input type="hidden" id="lend-book-id">
        <div class="form-group">
          <label>Borrower Name *</label>
          <input type="text" id="lend-borrower" required placeholder="Full name">
        </div>
        <div class="form-group">
          <label>Contact (email/phone)</label>
          <input type="text" id="lend-contact" placeholder="Optional">
        </div>
        <div class="form-group">
          <label>Lent Date *</label>
          <input type="date" id="lend-date" required>
        </div>
        <div class="form-group">
          <label>Due Date</label>
          <input type="date" id="lend-due">
        </div>
        <div class="form-group full">
          <label>Notes</label>
          <textarea id="lend-notes" placeholder="Any notes…"></textarea>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost modal-close">Cancel</button>
      <button class="btn btn-forest" id="lend-submit-btn">📤 Lend Book</button>
    </div>
  </div>
</div>

<div id="page-alerts"></div>

<script>
const STORAGE_KEY = 'bookshelf_last_filter';
let allGenres = <?= json_encode($genres) ?>;
let currentBooks = [];

async function loadStats() {
  const data = await ajax('api/stats.php');
  document.getElementById('s-total').textContent   = data.total_books;
  document.getElementById('s-lent').textContent    = data.lent_out;
  document.getElementById('s-genres').textContent  = data.genres;
  document.getElementById('s-overdue').textContent = data.overdue;
}

async function loadBooks() {
  const genreId = document.getElementById('genre-filter').value;
  const search  = document.getElementById('search-input').value.trim();

  // save previous filter
  const previousState = sessionStorage.getItem(STORAGE_KEY);
  if (previousState) {
    try {
      const prev = JSON.parse(previousState);
      if (prev.genreId || prev.search) {
        updateFilterMemory(prev); 
      }
    } catch(e) {}
  }

  const filterState = { genreId, search, genreName: document.getElementById('genre-filter').selectedOptions[0]?.text };
  sessionStorage.setItem(STORAGE_KEY, JSON.stringify(filterState));

  const params = new URLSearchParams();
  if (genreId) params.set('genre_id', genreId);
  if (search)  params.set('search', search);

  const container = document.getElementById('books-container');
  container.innerHTML = '<div class="loading-wrap"><div class="spinner"></div><span>Loading…</span></div>';

  const data = await ajax('api/books.php?' + params.toString());
  currentBooks = data.books || [];
  renderBooks(currentBooks);
}

function restoreFilterMemory() {
  const saved = sessionStorage.getItem(STORAGE_KEY);
  if (!saved) return;
  try {
    const { genreId, search } = JSON.parse(saved);
    if (genreId) document.getElementById('genre-filter').value = genreId;
    if (search)  document.getElementById('search-input').value  = search;
  } catch(e) {}
}

function updateFilterMemory({ genreId, search, genreName }) {
  const el = document.getElementById('filter-memory');
  if (!genreId && !search) { el.style.display = 'none'; return; }
  const parts = [];
  if (genreId) parts.push(`genre: "${genreName}"`);
  if (search)  parts.push(`search: "${search}"`);
  el.textContent = `Last filter — ${parts.join(', ')}`;
  el.style.display = 'inline';
}

function renderBooks(books) {
  const container = document.getElementById('books-container');
  if (!books.length) {
    container.innerHTML = `<div class="empty-state"><div class="empty-icon">📭</div><p>No books found. Try a different filter or <a href="add.php">add one</a>.</p></div>`;
    return;
  }
  const grid = document.createElement('div');
  grid.className = 'books-grid';
  books.forEach(book => {
    const card = document.createElement('div');
    card.className = 'book-card';
    card.innerHTML = `
      <div class="book-cover" style="background:${book.cover_color}">${escHtml(book.title)}</div>
      <div class="book-info">
        <div class="book-title">${escHtml(book.title)}</div>
        <div class="book-author">by ${escHtml(book.author)}</div>
        <div class="book-meta">${book.published_year || ''} ${book.pages ? '· ' + book.pages + ' pp' : ''}</div>
        ${book.genre_name ? `<span class="genre-tag">${escHtml(book.genre_name)}</span>` : ''}
        ${book.is_lent ? '<span class="book-badge lent">Lent Out</span>' : ''}
      </div>`;
    card.addEventListener('click', () => openBookDetail(book));
    grid.appendChild(card);
  });
  container.innerHTML = '';
  container.appendChild(grid);
}

function escHtml(str) {
  return (str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function openBookDetail(book) {
  const body   = document.getElementById('book-modal-body');
  const footer = document.getElementById('book-modal-footer');
  document.getElementById('modal-mode-label').textContent = '📖 Book Details';

  body.innerHTML = `
    <div style="display:flex;gap:1.5rem;align-items:flex-start;flex-wrap:wrap">
      <div style="width:80px;height:120px;background:${book.cover_color};border-radius:3px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-family:'Playfair Display',serif;font-size:0.7rem;color:rgba(255,255,255,0.9);text-align:center;padding:0.5rem;position:relative">
        <span style="position:absolute;left:0;top:0;bottom:0;width:8px;background:rgba(0,0,0,0.25)"></span>
        ${escHtml(book.title)}
      </div>
      <div style="flex:1;min-width:200px">
        <h2 style="font-family:'Playfair Display',serif;font-size:1.4rem;margin-bottom:0.25rem">${escHtml(book.title)}</h2>
        <p style="font-style:italic;color:var(--muted);margin-bottom:0.75rem">by ${escHtml(book.author)}</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.4rem 1.5rem;font-size:0.9rem;color:var(--slate)">
          ${book.genre_name ? `<div><strong>Genre:</strong> ${escHtml(book.genre_name)}</div>` : ''}
          ${book.pages ? `<div><strong>Pages:</strong> ${book.pages}</div>` : ''}
          ${book.published_year ? `<div><strong>Year:</strong> ${book.published_year}</div>` : ''}
          ${book.isbn ? `<div><strong>ISBN:</strong> ${escHtml(book.isbn)}</div>` : ''}
        </div>
        ${book.description ? `<p style="margin-top:0.75rem;font-size:0.95rem;color:var(--slate)">${escHtml(book.description)}</p>` : ''}
        ${book.is_lent ? '<span class="book-badge lent" style="margin-top:0.5rem">Currently Lent Out</span>' : ''}
      </div>
    </div>`;

  footer.innerHTML = '';
  const closeBtn = document.createElement('button');
  closeBtn.className = 'btn btn-ghost modal-close';
  closeBtn.textContent = 'Close';

  const editBtn = document.createElement('button');
  editBtn.className = 'btn btn-primary';
  editBtn.textContent = '✏️ Edit';
  editBtn.onclick = () => openEditModal(book);

  const deleteBtn = document.createElement('button');
  deleteBtn.className = 'btn btn-danger';
  deleteBtn.textContent = '🗑 Delete';
  deleteBtn.onclick = () => deleteBook(book);

  footer.appendChild(closeBtn);
  if (!book.is_lent) {
    const lendBtn = document.createElement('button');
    lendBtn.className = 'btn btn-forest';
    lendBtn.textContent = '📤 Lend';
    lendBtn.onclick = () => { closeModal('book-modal'); openLendModal(book.id); };
    footer.appendChild(lendBtn);
  }
  footer.appendChild(editBtn);
  footer.appendChild(deleteBtn);
  openModal('book-modal');
}

function openEditModal(book) {
  document.getElementById('modal-mode-label').textContent = '✏️ Edit Book';
  const genreOptions = allGenres.map(g =>
    `<option value="${g.id}" ${book.genre_id == g.id ? 'selected' : ''}>${escHtml(g.name)}</option>`
  ).join('');

  const body = document.getElementById('book-modal-body');
  body.innerHTML = `
    <form id="edit-form" class="form-grid">
      <div class="form-group"><label>Title *</label><input type="text" id="e-title" required value="${escHtml(book.title)}"></div>
      <div class="form-group"><label>Author *</label><input type="text" id="e-author" required value="${escHtml(book.author)}"></div>
      <div class="form-group"><label>Genre</label>
        <select id="e-genre"><option value="">— None —</option>${genreOptions}</select>
      </div>
      <div class="form-group"><label>Pages</label><input type="number" id="e-pages" data-type="int" min="1" value="${book.pages||''}"></div>
      <div class="form-group"><label>Published Year</label><input type="number" id="e-year" data-type="year" value="${book.published_year||''}"></div>
      <div class="form-group"><label>ISBN</label><input type="text" id="e-isbn" value="${escHtml(book.isbn||'')}"></div>
      <div class="form-group full"><label>Cover Color</label>
        <div class="color-row"><input type="color" id="e-color" value="${book.cover_color||'#4a6fa5'}"><span id="e-color-preview" style="padding:0.4rem 1rem;border-radius:3px;color:#fff;font-family:'Playfair Display',serif;font-size:0.8rem;background:${book.cover_color}">${escHtml(book.title)}</span></div>
      </div>
      <div class="form-group full"><label>Description</label><textarea id="e-desc">${escHtml(book.description||'')}</textarea></div>
    </form>`;

  document.getElementById('e-color').addEventListener('input', e => {
    const prev = document.getElementById('e-color-preview');
    prev.style.background = e.target.value;
  });

  const footer = document.getElementById('book-modal-footer');
  footer.innerHTML = '';
  const cancelBtn = document.createElement('button');
  cancelBtn.className = 'btn btn-ghost modal-close';
  cancelBtn.textContent = 'Cancel';
  const saveBtn = document.createElement('button');
  saveBtn.className = 'btn btn-primary';
  saveBtn.textContent = '💾 Save Changes';
  saveBtn.onclick = () => saveEdit(book.id);
  footer.appendChild(cancelBtn);
  footer.appendChild(saveBtn);
}

async function saveEdit(id) {
  const form = document.getElementById('edit-form');
  if (!validateForm(form)) return;
  const payload = {
    id,
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
    closeModal('book-modal');
    showAlert(document.getElementById('page-alerts'), 'Book updated successfully!');
    loadBooks(); loadStats();
  } else {
    showAlert(document.getElementById('page-alerts'), res.error || 'Update failed.', 'error');
  }
}

async function deleteBook(book) {
  closeModal('book-modal');
  const ok = await confirmAction('Delete Book', `Delete "${book.title}" by ${book.author}? This cannot be undone.`);
  if (!ok) return;
  const res = await ajax('api/books.php?id=' + book.id, 'DELETE');
  if (res.success) {
    showAlert(document.getElementById('page-alerts'), 'Book deleted.');
    loadBooks(); loadStats();
  } else {
    showAlert(document.getElementById('page-alerts'), res.error || 'Delete failed.', 'error');
  }
}

function openLendModal(bookId) {
  document.getElementById('lend-book-id').value = bookId;
  document.getElementById('lend-date').value = new Date().toISOString().slice(0,10);
  document.getElementById('lend-borrower').value = '';
  document.getElementById('lend-contact').value  = '';
  document.getElementById('lend-due').value      = '';
  document.getElementById('lend-notes').value    = '';
  openModal('lend-modal');
}

document.getElementById('lend-submit-btn').onclick = async () => {
  const form = document.getElementById('lend-form');
  if (!validateForm(form)) return;
  const payload = {
    book_id:          parseInt(document.getElementById('lend-book-id').value),
    borrower_name:    document.getElementById('lend-borrower').value.trim(),
    borrower_contact: document.getElementById('lend-contact').value.trim(),
    lent_date:        document.getElementById('lend-date').value,
    due_date:         document.getElementById('lend-due').value,
    notes:            document.getElementById('lend-notes').value.trim(),
  };
  const res = await ajax('api/lendings.php', 'POST', payload);
  if (res.success) {
    closeModal('lend-modal');
    showAlert(document.getElementById('page-alerts'), 'Book lent successfully!', 'success');
    loadBooks(); loadStats();
  } else {
    showAlert(document.getElementById('page-alerts'), res.error || 'Failed to lend.', 'error');
  }
};

let searchTimer;
document.getElementById('search-input').addEventListener('input', () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(loadBooks, 400);
});
document.getElementById('genre-filter').addEventListener('change', loadBooks);

restoreFilterMemory();
Promise.all([loadStats(), loadBooks()]);
</script>

<?php require_once 'footer.php'; ?>
