<?php
require_once 'db.php';

if (isset($_GET['reset'])) {
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    $conn->query("TRUNCATE TABLE lendings");
    $conn->query("TRUNCATE TABLE books");
    $conn->query("TRUNCATE TABLE genres");
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    log_ok("✓ Tables cleared");
}

$output = [];
$errors = [];

function log_ok($msg) { global $output; $output[] = $msg; }
function log_err($msg) { global $errors; $errors[] = $msg; }

// Create tables
$conn->query("CREATE TABLE IF NOT EXISTS genres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
)");
$conn->query("CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    pages INT,
    genre_id INT,
    isbn VARCHAR(20),
    published_year INT,
    description TEXT,
    cover_color VARCHAR(7) DEFAULT '#4a6fa5',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE SET NULL
)");
$conn->query("CREATE TABLE IF NOT EXISTS lendings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    borrower_name VARCHAR(255) NOT NULL,
    borrower_contact VARCHAR(255),
    lent_date DATE NOT NULL,
    due_date DATE,
    returned_date DATE,
    notes TEXT,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
)");
log_ok("✓ Tables created/verified");

// ── 1. GENRES ────────────────────────────────────────────────────────────────
$genres = [
    'Fiction', 'Non-Fiction', 'Science Fiction', 'Fantasy', 'Mystery',
    'Thriller', 'Biography', 'History', 'Science', 'Philosophy',
    'Poetry', 'Romance', 'Horror', 'Self-Help', 'Classics'
];

$genre_ids = [];
foreach ($genres as $name) {
    $stmt = $conn->prepare("INSERT IGNORE INTO genres (name) VALUES (?)");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    // Get ID whether inserted or already existed
    $s2 = $conn->prepare("SELECT id FROM genres WHERE name = ?");
    $s2->bind_param("s", $name);
    $s2->execute();
    $row = $s2->get_result()->fetch_assoc();
    $genre_ids[$name] = (int)$row['id'];
    $s2->close();
    $stmt->close();
}
log_ok("✓ Genres seeded (" . count($genres) . " total)");

// ── 2. BOOKS ─────────────────────────────────────────────────────────────────
$books = [
    // Classics
    ['title'=>'Crime and Punishment',         'author'=>'Fyodor Dostoevsky',    'genre'=>'Classics',       'pages'=>671,  'year'=>1866, 'isbn'=>'978-0140449136', 'color'=>'#2c3e50', 'desc'=>'A psychological portrait of a young man who commits a murder and grapples with guilt.'],
    ['title'=>'Anna Karenina',                'author'=>'Leo Tolstoy',          'genre'=>'Classics',       'pages'=>864,  'year'=>1878, 'isbn'=>'978-0143035008', 'color'=>'#8e3a2f', 'desc'=>'A tragic love story set in Russian high society.'],
    ['title'=>'Madame Bovary',                'author'=>'Gustave Flaubert',     'genre'=>'Classics',       'pages'=>329,  'year'=>1857, 'isbn'=>'978-0140449129', 'color'=>'#5b4a6f', 'desc'=>'A provincial doctor\'s wife seeks escape from a boring marriage through affairs and fantasy.'],
    ['title'=>'Don Quixote',                  'author'=>'Miguel de Cervantes',  'genre'=>'Classics',       'pages'=>982,  'year'=>1605, 'isbn'=>'978-0060934347', 'color'=>'#b5651d', 'desc'=>'The misadventures of a nobleman who believes himself to be a knight-errant.'],

    // Fiction
    ['title'=>'The Road',                     'author'=>'Cormac McCarthy',      'genre'=>'Fiction',        'pages'=>287,  'year'=>2006, 'isbn'=>'978-0307387899', 'color'=>'#4a4a4a', 'desc'=>'A father and son journey through a post-apocalyptic America.'],
    ['title'=>'Normal People',                'author'=>'Sally Rooney',         'genre'=>'Fiction',        'pages'=>273,  'year'=>2018, 'isbn'=>'978-0571334650', 'color'=>'#e8d5b7', 'desc'=>'Two young Irish people navigate love, class, and connection from school through university.'],
    ['title'=>'The Midnight Library',         'author'=>'Matt Haig',            'genre'=>'Fiction',        'pages'=>304,  'year'=>2020, 'isbn'=>'978-0525559474', 'color'=>'#1a3a4a', 'desc'=>'A library between life and death where each book represents an unchosen life.'],
    ['title'=>'Piranesi',                     'author'=>'Susanna Clarke',       'genre'=>'Fiction',        'pages'=>272,  'year'=>2020, 'isbn'=>'978-1526622426', 'color'=>'#3d6b8a', 'desc'=>'A man lives in a mysterious house with infinite halls and statues.'],

    // Science Fiction
    ['title'=>'Dune',                         'author'=>'Frank Herbert',        'genre'=>'Science Fiction','pages'=>688,  'year'=>1965, 'isbn'=>'978-0441013593', 'color'=>'#c4a35a', 'desc'=>'On the desert planet Arrakis, young Paul Atreides confronts his destiny.'],
    ['title'=>'The Left Hand of Darkness',    'author'=>'Ursula K. Le Guin',   'genre'=>'Science Fiction','pages'=>304,  'year'=>1969, 'isbn'=>'978-0441478125', 'color'=>'#5a7a8a', 'desc'=>'An envoy visits a world whose inhabitants have no fixed gender.'],
    ['title'=>'Hyperion',                     'author'=>'Dan Simmons',          'genre'=>'Science Fiction','pages'=>482,  'year'=>1989, 'isbn'=>'978-0553283686', 'color'=>'#2a4a6a', 'desc'=>'Seven pilgrims share their stories on a journey to the mysterious Shrike.'],
    ['title'=>'Blindsight',                   'author'=>'Peter Watts',          'genre'=>'Science Fiction','pages'=>384,  'year'=>2006, 'isbn'=>'978-0765319647', 'color'=>'#1a2a3a', 'desc'=>'First contact with alien intelligence raises questions about consciousness itself.'],

    // Fantasy
    ['title'=>'The Name of the Wind',         'author'=>'Patrick Rothfuss',     'genre'=>'Fantasy',        'pages'=>662,  'year'=>2007, 'isbn'=>'978-0756404741', 'color'=>'#4a3a2a', 'desc'=>'The legendary Kvothe recounts his life from his childhood in a troupe of traveling players.'],
    ['title'=>'Jonathan Strange & Mr Norrell','author'=>'Susanna Clarke',       'genre'=>'Fantasy',        'pages'=>782,  'year'=>2004, 'isbn'=>'978-0765356154', 'color'=>'#2c2c5a', 'desc'=>'Two magicians attempt to restore magic to England during the Napoleonic Wars.'],
    ['title'=>'The Way of Kings',             'author'=>'Brandon Sanderson',    'genre'=>'Fantasy',        'pages'=>1007, 'year'=>2010, 'isbn'=>'978-0765326355', 'color'=>'#4a6a5a', 'desc'=>'On a world of stone and storms, three people are bound by an ancient war.'],
    ['title'=>'American Gods',                'author'=>'Neil Gaiman',          'genre'=>'Fantasy',        'pages'=>465,  'year'=>2001, 'isbn'=>'978-0062572233', 'color'=>'#3a2a4a', 'desc'=>'Old gods versus new in a road trip across a mythological America.'],

    // Mystery & Thriller
    ['title'=>'Gone Girl',                    'author'=>'Gillian Flynn',        'genre'=>'Thriller',       'pages'=>422,  'year'=>2012, 'isbn'=>'978-0307588371', 'color'=>'#1a1a2a', 'desc'=>'On their fifth anniversary, Nick Dunne\'s wife Amy disappears.'],
    ['title'=>'The Girl with the Dragon Tattoo','author'=>'Stieg Larsson',      'genre'=>'Thriller',       'pages'=>672,  'year'=>2005, 'isbn'=>'978-0307949486', 'color'=>'#2a1a1a', 'desc'=>'A journalist and a hacker investigate a wealthy family\'s dark secrets.'],
    ['title'=>'And Then There Were None',     'author'=>'Agatha Christie',      'genre'=>'Mystery',        'pages'=>272,  'year'=>1939, 'isbn'=>'978-0062073488', 'color'=>'#3a2a1a', 'desc'=>'Ten strangers are lured to an island and begin dying one by one.'],
    ['title'=>'The Thursday Murder Club',     'author'=>'Richard Osman',        'genre'=>'Mystery',        'pages'=>382,  'year'=>2020, 'isbn'=>'978-0241425459', 'color'=>'#5a4a3a', 'desc'=>'Four retirees in a peaceful village solve cold cases — until they get a real one.'],

    // Horror
    ['title'=>'The Haunting of Hill House',   'author'=>'Shirley Jackson',      'genre'=>'Horror',         'pages'=>246,  'year'=>1959, 'isbn'=>'978-0143039983', 'color'=>'#2a2a2a', 'desc'=>'Four people investigate a notoriously haunted mansion.'],
    ['title'=>'It',                           'author'=>'Stephen King',         'genre'=>'Horror',         'pages'=>1138, 'year'=>1986, 'isbn'=>'978-1501142970', 'color'=>'#1a0a0a', 'desc'=>'In Derry, Maine, a group of children are hunted by an evil that takes the form of a clown.'],

    // Biography
    ['title'=>'The Diary of a Young Girl',    'author'=>'Anne Frank',           'genre'=>'Biography',      'pages'=>283,  'year'=>1947, 'isbn'=>'978-0553296983', 'color'=>'#8a6a3a', 'desc'=>'The diary kept by Anne Frank while hiding from the Nazis in Amsterdam.'],
    ['title'=>'Educated',                     'author'=>'Tara Westover',        'genre'=>'Biography',      'pages'=>334,  'year'=>2018, 'isbn'=>'978-0399590504', 'color'=>'#6a8a5a', 'desc'=>'A woman who grew up in a survivalist family in Idaho and educated herself into Cambridge.'],
    ['title'=>'The Autobiography of Malcolm X','author'=>'Malcolm X',           'genre'=>'Biography',      'pages'=>460,  'year'=>1965, 'isbn'=>'978-0345350688', 'color'=>'#4a2a1a', 'desc'=>'The powerful story of one of the most influential figures of the 20th century.'],

    // History
    ['title'=>'Sapiens',                      'author'=>'Yuval Noah Harari',   'genre'=>'History',        'pages'=>443,  'year'=>2011, 'isbn'=>'978-0062316097', 'color'=>'#7a5a3a', 'desc'=>'A brief history of humankind from the Stone Age to the 21st century.'],
    ['title'=>'The Guns of August',           'author'=>'Barbara Tuchman',     'genre'=>'History',        'pages'=>511,  'year'=>1962, 'isbn'=>'978-0345476098', 'color'=>'#3a4a5a', 'desc'=>'A gripping account of the first month of World War I.'],

    // Science
    ['title'=>'A Brief History of Time',      'author'=>'Stephen Hawking',     'genre'=>'Science',        'pages'=>212,  'year'=>1988, 'isbn'=>'978-0553380163', 'color'=>'#1a2a4a', 'desc'=>'An exploration of cosmology — black holes, the Big Bang, and the nature of time.'],
    ['title'=>'The Selfish Gene',             'author'=>'Richard Dawkins',     'genre'=>'Science',        'pages'=>360,  'year'=>1976, 'isbn'=>'978-0198788607', 'color'=>'#2a4a2a', 'desc'=>'A groundbreaking work presenting evolution from the gene\'s perspective.'],
    ['title'=>'Thinking, Fast and Slow',      'author'=>'Daniel Kahneman',     'genre'=>'Non-Fiction',    'pages'=>499,  'year'=>2011, 'isbn'=>'978-0374533557', 'color'=>'#4a3a6a', 'desc'=>'Two systems of thought shape our decisions — and how to tell them apart.'],

    // Philosophy
    ['title'=>'Meditations',                  'author'=>'Marcus Aurelius',      'genre'=>'Philosophy',     'pages'=>254,  'year'=>180,  'isbn'=>'978-0140449334', 'color'=>'#5a5a4a', 'desc'=>'Personal writings of the Roman emperor as a source of Stoic philosophy.'],
    ['title'=>'The Myth of Sisyphus',         'author'=>'Albert Camus',         'genre'=>'Philosophy',     'pages'=>212,  'year'=>1942, 'isbn'=>'978-0679733737', 'color'=>'#4a5a6a', 'desc'=>'An exploration of absurdism and the question of whether life is worth living.'],

    // Self-Help
    ['title'=>'Atomic Habits',                'author'=>'James Clear',          'genre'=>'Self-Help',      'pages'=>320,  'year'=>2018, 'isbn'=>'978-0735211292', 'color'=>'#3a5a4a', 'desc'=>'A practical guide to building good habits and breaking bad ones through tiny changes.'],
    ['title'=>'Man\'s Search for Meaning',    'author'=>'Viktor Frankl',        'genre'=>'Self-Help',      'pages'=>165,  'year'=>1946, 'isbn'=>'978-0807014271', 'color'=>'#5a4a3a', 'desc'=>'A psychiatrist\'s memoir of surviving Auschwitz and the philosophy of logotherapy.'],

    // Poetry
    ['title'=>'Leaves of Grass',              'author'=>'Walt Whitman',         'genre'=>'Poetry',         'pages'=>462,  'year'=>1855, 'isbn'=>'978-0140421996', 'color'=>'#4a6a3a', 'desc'=>'A landmark collection celebrating democracy, nature, love, and the self.'],
    ['title'=>'Milk and Honey',               'author'=>'Rupi Kaur',            'genre'=>'Poetry',         'pages'=>208,  'year'=>2014, 'isbn'=>'978-1449486792', 'color'=>'#c8a882', 'desc'=>'A collection of poetry about survival and the experience of violence and healing.'],

    // Romance
    ['title'=>'Pride and Prejudice',          'author'=>'Jane Austen',          'genre'=>'Romance',        'pages'=>432,  'year'=>1813, 'isbn'=>'978-0141439518', 'color'=>'#8a6a8a', 'desc'=>'Elizabeth Bennet and Mr. Darcy navigate pride, prejudice, and love in Regency England.'],
    ['title'=>'Outlander',                    'author'=>'Diana Gabaldon',       'genre'=>'Romance',        'pages'=>850,  'year'=>1991, 'isbn'=>'978-0440212560', 'color'=>'#6a4a5a', 'desc'=>'A WWII nurse is swept back in time to 18th-century Scotland.'],
];

$stmt = $conn->prepare("INSERT IGNORE INTO books (title, author, genre_id, pages, published_year, isbn, cover_color, description) VALUES (?,?,?,?,?,?,?,?)");
$inserted = 0;
$skipped  = 0;

foreach ($books as $b) {
    $gid = isset($b['genre']) && isset($genre_ids[$b['genre']]) ? $genre_ids[$b['genre']] : null;
    $stmt->bind_param("ssiissss",
        $b['title'], $b['author'], $gid,
        $b['pages'], $b['year'], $b['isbn'],
        $b['color'], $b['desc']
    );
    $stmt->execute();
    if ($stmt->affected_rows > 0) $inserted++;
    else $skipped++;
}
$stmt->close();
log_ok("✓ Books seeded ($inserted inserted, $skipped already existed)");

// ── 3. SAMPLE LENDINGS ───────────────────────────────────────────────────────
// Pick a few books by title and create lendings for them
$lending_data = [
    ['title'=>'Dune',              'borrower'=>'Alex Muresan',    'contact'=>'alex@example.com',    'lent_date'=>'2026-04-10', 'due_date'=>'2026-05-10', 'returned'=>null,         'notes'=>'Lent at the office'],
    ['title'=>'Atomic Habits',     'borrower'=>'Ioana Pop',       'contact'=>'0740-123-456',         'lent_date'=>'2026-03-15', 'due_date'=>'2026-04-15', 'returned'=>'2026-04-12', 'notes'=>''],
    ['title'=>'Sapiens',           'borrower'=>'Mihai Constantin', 'contact'=>'mihai.c@gmail.com',   'lent_date'=>'2026-04-20', 'due_date'=>'2026-05-20', 'returned'=>null,         'notes'=>'Very interested in the first chapter'],
    ['title'=>'Gone Girl',         'borrower'=>'Diana Lupu',      'contact'=>'diana.lupu@yahoo.com', 'lent_date'=>'2026-02-01', 'due_date'=>'2026-03-01', 'returned'=>'2026-02-28', 'notes'=>'Said it was unputdownable'],
    ['title'=>'The Road',          'borrower'=>'Radu Ionescu',    'contact'=>'0752-987-654',         'lent_date'=>'2026-04-01', 'due_date'=>'2026-04-30', 'returned'=>null,         'notes'=>'Overdue — send reminder'],
    ['title'=>'Meditations',       'borrower'=>'Andrei Vlad',     'contact'=>'andrei@startup.io',    'lent_date'=>'2026-05-01', 'due_date'=>null,          'returned'=>null,         'notes'=>'Borrowed indefinitely'],
];

$inserted_l = 0;
foreach ($lending_data as $l) {
    // Find book id
    $s = $conn->prepare("SELECT id FROM books WHERE title = ? LIMIT 1");
    $s->bind_param("s", $l['title']);
    $s->execute();
    $row = $s->get_result()->fetch_assoc();
    $s->close();
    if (!$row) continue;
    $book_id = $row['id'];

    // Check if a lending already exists for this book+borrower
    $chk = $conn->prepare("SELECT id FROM lendings WHERE book_id=? AND borrower_name=? LIMIT 1");
    $chk->bind_param("is", $book_id, $l['borrower']);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) { $chk->close(); continue; }
    $chk->close();

    $stmt = $conn->prepare("INSERT INTO lendings (book_id, borrower_name, borrower_contact, lent_date, due_date, returned_date, notes) VALUES (?,?,?,?,?,?,?)");
    $stmt->bind_param("issssss",
        $book_id, $l['borrower'], $l['contact'],
        $l['lent_date'], $l['due_date'], $l['returned'], $l['notes']
    );
    $stmt->execute();
    if ($stmt->affected_rows > 0) $inserted_l++;
    $stmt->close();
}
log_ok("✓ Lendings seeded ($inserted_l inserted)");

$conn->close();

// ── OUTPUT ───────────────────────────────────────────────────────────────────
$all_ok = empty($errors);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Seed Database — BookShelf</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    body { display:flex; align-items:center; justify-content:center; min-height:100vh; padding:2rem; }
    .seed-card { max-width:520px; width:100%; }
    .seed-card h1 { font-family:'Playfair Display',serif; font-size:1.8rem; margin-bottom:0.25rem; color:var(--gold); }
    .seed-card p.sub { color:var(--muted); font-style:italic; margin-bottom:1.5rem; }
    .log-line { padding:0.5rem 0.75rem; border-left:3px solid var(--forest); margin-bottom:0.5rem; background:var(--parchment); border-radius:0 3px 3px 0; font-size:0.95rem; }
    .log-line.err { border-color:var(--burgundy); }
    .actions { display:flex; gap:1rem; margin-top:1.5rem; }
  </style>
</head>
<body>
<div class="card seed-card">
  <h1>📚 Seed Complete</h1>
  <p class="sub">Database has been populated with sample data.</p>

  <?php foreach ($output as $line): ?>
    <div class="log-line"><?= htmlspecialchars($line) ?></div>
  <?php endforeach; ?>
  <?php foreach ($errors as $line): ?>
    <div class="log-line err">✗ <?= htmlspecialchars($line) ?></div>
  <?php endforeach; ?>

  <div class="actions">
    <a href="index.php" class="btn btn-primary">Browse Library →</a>
    <a href="seed.php" class="btn btn-ghost">Run Again</a>
  </div>
</div>
</body>
</html>
