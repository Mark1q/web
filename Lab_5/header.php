<?php
$pages = [
    'index.php'     => 'Browse',
    'add.php'       => 'Add Book',
    'manage.php'    => 'Manage',
    'lendings.php'  => 'Lendings',
    'genres.php'    => 'Genres',
];
$current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'My Library') ?> — BookShelf</title>
  <link rel="stylesheet" href="css/style.css">
<script>
async function ajax(url, method = 'GET', data = null) {
  const opts = { method, headers: {} };
  if (data) {
    if (data instanceof FormData) {
      opts.body = data;
    } else {
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(data);
    }
  }
  const res = await fetch(url, opts);
  return res.json();
}

function validateForm(form) {
  let valid = true;
  form.querySelectorAll('.error-msg').forEach(e => e.remove());
  form.querySelectorAll('[required]').forEach(input => {
    input.classList.remove('error');
    if (!input.value.trim()) {
      input.classList.add('error');
      const msg = document.createElement('span');
      msg.className = 'error-msg';
      msg.textContent = 'This field is required.';
      input.parentNode.appendChild(msg);
      valid = false;
    }
  });
  form.querySelectorAll('[data-type="int"]').forEach(input => {
    if (input.value && (!/^\d+$/.test(input.value) || parseInt(input.value) <= 0)) {
      input.classList.add('error');
      const msg = document.createElement('span');
      msg.className = 'error-msg';
      msg.textContent = 'Must be a positive number.';
      input.parentNode.appendChild(msg);
      valid = false;
    }
  });
  form.querySelectorAll('[data-type="year"]').forEach(input => {
    const y = parseInt(input.value);
    if (input.value && (y < 1000 || y > new Date().getFullYear() + 1)) {
      input.classList.add('error');
      const msg = document.createElement('span');
      msg.className = 'error-msg';
      msg.textContent = `Enter a valid year (1000–${new Date().getFullYear()}).`;
      input.parentNode.appendChild(msg);
      valid = false;
    }
  });
  return valid;
}

function showAlert(container, message, type = 'success') {
  const icons = { success: '✓', error: '✗', info: 'ℹ' };
  const el = document.createElement('div');
  el.className = `alert alert-${type}`;
  el.innerHTML = `<span>${icons[type]}</span> ${message}`;
  container.prepend(el);
  setTimeout(() => el.remove(), 4000);
}

function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function confirmAction(title, message, okLabel = 'Delete') {
  return new Promise(resolve => {
    const overlay = document.getElementById('confirm-overlay');
    document.getElementById('confirm-title').textContent = title;
    document.getElementById('confirm-message').textContent = message;
    document.getElementById('confirm-ok').textContent = okLabel;
    overlay.classList.add('open');
    const ok     = document.getElementById('confirm-ok');
    const cancel = document.getElementById('confirm-cancel');
    const close  = () => overlay.classList.remove('open');
    ok.onclick     = () => { close(); resolve(true); };
    cancel.onclick = () => { close(); resolve(false); };
    overlay.onclick = e => { if (e.target === overlay) { close(); resolve(false); } };
  });
}

document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-close')) {
    e.target.closest('.modal-overlay').classList.remove('open');
  }
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('open');
  }
});
</script>
</head>
<body>
<header>
  <div class="header-inner">
    <a class="logo" href="index.php">📚 Book<span>Shelf</span></a>
    <nav>
      <?php foreach ($pages as $file => $label): ?>
        <a href="<?= $file ?>" class="<?= $current === $file ? 'active' : '' ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </nav>
  </div>
</header>
<main>
