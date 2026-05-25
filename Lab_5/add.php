<?php
$pageTitle = 'Add a Book';
require_once 'header.php';
require_once 'db.php';
$genres = [];
$res = $conn->query("SELECT * FROM genres ORDER BY name");
while ($row = $res->fetch_assoc()) $genres[] = $row;
$conn->close();
?>

<h1 class="page-title">Add a New Book</h1>
<p class="page-subtitle">Expand your collection</p>

<div id="alerts"></div>

<div class="card" style="max-width:720px">
  <form id="add-form" class="form-grid" novalidate>
    <div class="form-group">
      <label>Title *</label>
      <input type="text" id="f-title" required placeholder="e.g. The Name of the Wind">
    </div>
    <div class="form-group">
      <label>Author *</label>
      <input type="text" id="f-author" required placeholder="e.g. Patrick Rothfuss">
    </div>
    <div class="form-group">
      <label>Genre</label>
      <select id="f-genre">
        <option value="">— Select genre —</option>
        <?php foreach ($genres as $g): ?>
          <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Number of Pages</label>
      <input type="number" id="f-pages" data-type="int" min="1" placeholder="e.g. 662">
    </div>
    <div class="form-group">
      <label>Published Year</label>
      <input type="number" id="f-year" data-type="year" min="1000" max="<?= date('Y') ?>" placeholder="e.g. 2007">
    </div>
    <div class="form-group">
      <label>ISBN</label>
      <input type="text" id="f-isbn" placeholder="e.g. 978-0756404741">
    </div>
    <div class="form-group full">
      <label>Cover Color</label>
      <div class="color-row">
        <input type="color" id="f-color" value="#4a6fa5">
        <div id="cover-preview" style="height:80px;width:56px;border-radius:3px;background:#4a6fa5;display:flex;align-items:center;justify-content:center;padding:0.4rem;font-family:'Playfair Display',serif;font-size:0.65rem;color:rgba(255,255,255,0.9);text-align:center;position:relative;overflow:hidden">
          <span style="position:absolute;left:0;top:0;bottom:0;width:6px;background:rgba(0,0,0,0.25)"></span>
          Preview
        </div>
      </div>
    </div>
    <div class="form-group full">
      <label>Description / Notes</label>
      <textarea id="f-desc" placeholder="Brief synopsis or personal notes…"></textarea>
    </div>
  </form>
  <hr>
  <div style="display:flex;gap:1rem;justify-content:flex-end">
    <a href="index.php" class="btn btn-ghost">Cancel</a>
    <button class="btn btn-primary" id="save-btn">📚 Add Book</button>
  </div>
</div>

<script>
const titleInput = document.getElementById('f-title');
const colorInput = document.getElementById('f-color');
const preview    = document.getElementById('cover-preview');

titleInput.addEventListener('input', () => {
  const text = preview.querySelector('span');
  preview.textContent = titleInput.value || 'Preview';
  if (text) preview.appendChild(text);
});
colorInput.addEventListener('input', () => {
  preview.style.background = colorInput.value;
});

document.getElementById('save-btn').addEventListener('click', async () => {
  const form = document.getElementById('add-form');
  if (!validateForm(form)) return;

  const payload = {
    title:          document.getElementById('f-title').value.trim(),
    author:         document.getElementById('f-author').value.trim(),
    genre_id:       document.getElementById('f-genre').value,
    pages:          document.getElementById('f-pages').value,
    published_year: document.getElementById('f-year').value,
    isbn:           document.getElementById('f-isbn').value.trim(),
    cover_color:    document.getElementById('f-color').value,
    description:    document.getElementById('f-desc').value.trim(),
  };

  const btn = document.getElementById('save-btn');
  btn.disabled = true; btn.textContent = 'Saving…';

  const res = await ajax('api/books.php', 'POST', payload);
  btn.disabled = false; btn.textContent = '📚 Add Book';

  if (res.success) {
    showAlert(document.getElementById('alerts'), `Book added successfully! <a href="index.php">Browse library →</a>`, 'success');
    form.querySelectorAll('input, textarea, select').forEach(el => el.value = '');
    document.getElementById('f-color').value = '#4a6fa5';
    preview.style.background = '#4a6fa5';
  } else {
    showAlert(document.getElementById('alerts'), res.error || 'Failed to add book.', 'error');
  }
});
</script>

<?php require_once 'footer.php'; ?>
