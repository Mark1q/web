<?php
$pageTitle = 'Lendings';
require_once 'header.php';
require_once 'db.php';

// get all books for the dropdown
$books_list = [];
$res = $conn->query("SELECT id, title, author FROM books ORDER BY title");
while ($row = $res->fetch_assoc()) $books_list[] = $row;
$conn->close();
?>

<h1 class="page-title">Book Lendings</h1>
<p class="page-subtitle">Track who has borrowed your books</p>

<div id="alerts"></div>

<div style="display:flex;gap:1rem;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap">
  <div style="display:flex;gap:0.5rem">
    <button class="btn btn-primary btn-sm" id="tab-active" onclick="switchTab('active')">📤 Currently Out</button>
    <button class="btn btn-ghost btn-sm"   id="tab-all"    onclick="switchTab('all')">📋 All History</button>
  </div>
  <button class="btn btn-forest btn-sm" onclick="openModal('lend-modal')" style="margin-left:auto">+ New Lending</button>
</div>

<div id="lend-container">
  <div class="loading-wrap"><div class="spinner"></div> Loading…</div>
</div>

<!-- new Lending Modal -->
<div class="modal-overlay" id="lend-modal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">📤 New Lending</span>
      <button class="modal-close">✕</button>
    </div>
    <div class="modal-body">
      <form id="lend-form" class="form-grid" novalidate>
        <div class="form-group full">
          <label>Book *</label>
          <select id="l-book" required>
            <option value="">— Select a book —</option>
            <?php foreach ($books_list as $b): ?>
              <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['title']) ?> — <?= htmlspecialchars($b['author']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Borrower Name *</label>
          <input type="text" id="l-borrower" required placeholder="Full name">
        </div>
        <div class="form-group">
          <label>Contact (email/phone)</label>
          <input type="text" id="l-contact" placeholder="Optional">
        </div>
        <div class="form-group">
          <label>Lent Date *</label>
          <input type="date" id="l-date" required>
        </div>
        <div class="form-group">
          <label>Due Date</label>
          <input type="date" id="l-due">
        </div>
        <div class="form-group full">
          <label>Notes</label>
          <textarea id="l-notes" placeholder="Any notes about this lending…"></textarea>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost modal-close">Cancel</button>
      <button class="btn btn-forest" id="lend-submit">📤 Record Lending</button>
    </div>
  </div>
</div>

<script>
let activeTab = 'active';

function switchTab(tab) {
  activeTab = tab;
  document.getElementById('tab-active').className = tab === 'active' ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm';
  document.getElementById('tab-all').className    = tab === 'all'    ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm';
  loadLendings();
}

async function loadLendings() {
  document.getElementById('lend-container').innerHTML = '<div class="loading-wrap"><div class="spinner"></div> Loading…</div>';
  const url = 'api/lendings.php' + (activeTab === 'active' ? '?active=1' : '');
  const data = await ajax(url);
  renderLendings(data.lendings || []);
}

function renderLendings(lendings) {
  const c = document.getElementById('lend-container');
  if (!lendings.length) {
    c.innerHTML = `<div class="empty-state"><div class="empty-icon">${activeTab === 'active' ? '📚' : '📋'}</div><p>${activeTab === 'active' ? 'No books currently lent out.' : 'No lending history yet.'}</p></div>`;
    return;
  }
  const today = new Date().toISOString().slice(0,10);
  const rows = lendings.map(l => {
    const returned = !!l.returned_date;
    const overdue  = !returned && l.due_date && l.due_date < today;
    const statusDot = returned ? 'returned' : 'active';
    const statusLabel = returned ? `Returned ${l.returned_date}` : (overdue ? '⚠ Overdue' : 'Out');
    return `
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:0.75rem">
            <div style="width:24px;height:36px;background:${l.cover_color};border-radius:2px;flex-shrink:0"></div>
            <div>
              <div style="font-weight:600;font-family:'Playfair Display',serif;font-size:0.95rem">${esc(l.title)}</div>
              <div style="font-size:0.8rem;color:var(--muted);font-style:italic">${esc(l.author)}</div>
            </div>
          </div>
        </td>
        <td>
          <div style="font-weight:600">${esc(l.borrower_name)}</div>
          ${l.borrower_contact ? `<div style="font-size:0.8rem;color:var(--muted)">${esc(l.borrower_contact)}</div>` : ''}
        </td>
        <td>${l.lent_date}</td>
        <td style="color:${overdue ? 'var(--burgundy)' : 'inherit'};font-weight:${overdue ? '600' : 'normal'}">${l.due_date || '—'}</td>
        <td>
          <span class="status-dot ${statusDot}"></span>
          <span style="color:${overdue ? 'var(--burgundy)' : 'inherit'}">${statusLabel}</span>
        </td>
        <td class="td-actions">
          ${!returned ? `<button class="btn btn-forest btn-sm" onclick="markReturned(${l.id})">✓ Returned</button>` : ''}
          <button class="btn btn-danger btn-sm" onclick="deleteLending(${l.id})">🗑</button>
        </td>
      </tr>`;
  }).join('');

  c.innerHTML = `
    <div class="table-wrap">
      <table>
        <thead><tr><th>Book</th><th>Borrower</th><th>Lent</th><th>Due</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>${rows}</tbody>
      </table>
    </div>`;
}

function esc(s) { return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

async function markReturned(id) {
  const ok = await confirmAction('Mark as Returned', 'Confirm that this book has been returned?', 'Yes, Returned');
  if (!ok) return;
  const res = await ajax('api/lendings.php', 'PUT', { id, returned_date: new Date().toISOString().slice(0,10) });
  if (res.success) {
    showAlert(document.getElementById('alerts'), 'Book marked as returned!');
    loadLendings();
  } else {
    showAlert(document.getElementById('alerts'), res.error || 'Failed.', 'error');
  }
}

async function deleteLending(id) {
  const ok = await confirmAction('Delete Lending Record', 'Remove this lending record permanently?');
  if (!ok) return;
  const res = await ajax('api/lendings.php?id=' + id, 'DELETE');
  if (res.success) {
    showAlert(document.getElementById('alerts'), 'Record deleted.');
    loadLendings();
  } else {
    showAlert(document.getElementById('alerts'), res.error || 'Delete failed.', 'error');
  }
}

// new lending submit
document.getElementById('l-date').value = new Date().toISOString().slice(0,10);
document.getElementById('lend-submit').onclick = async () => {
  const form = document.getElementById('lend-form');
  if (!validateForm(form)) return;
  const payload = {
    book_id:          parseInt(document.getElementById('l-book').value),
    borrower_name:    document.getElementById('l-borrower').value.trim(),
    borrower_contact: document.getElementById('l-contact').value.trim(),
    lent_date:        document.getElementById('l-date').value,
    due_date:         document.getElementById('l-due').value,
    notes:            document.getElementById('l-notes').value.trim(),
  };
  const res = await ajax('api/lendings.php', 'POST', payload);
  if (res.success) {
    closeModal('lend-modal');
    showAlert(document.getElementById('alerts'), 'Lending recorded!');
    ['l-book','l-borrower','l-contact','l-due','l-notes'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('l-date').value = new Date().toISOString().slice(0,10);
    loadLendings();
  } else {
    showAlert(document.getElementById('alerts'), res.error || 'Failed.', 'error');
  }
};

loadLendings();
</script>

<?php require_once 'footer.php'; ?>
