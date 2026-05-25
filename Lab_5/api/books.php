<?php
header('Content-Type: application/json');
require_once '../db.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $genre_id = isset($_GET['genre_id']) ? (int)$_GET['genre_id'] : 0;
        $search   = isset($_GET['search'])   ? trim($_GET['search'])   : '';

        $sql = "SELECT b.*, g.name AS genre_name FROM books b LEFT JOIN genres g ON b.genre_id = g.id WHERE 1=1";
        $params = [];
        $types  = '';

        if ($genre_id > 0) {
            $sql .= " AND b.genre_id = ?";
            $params[] = $genre_id;
            $types .= 'i';
        }
        if ($search !== '') {
            $sql .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
            $like = "%$search%";
            $params[] = $like; $params[] = $like; $params[] = $like;
            $types .= 'sss';
        }

        $sql .= " ORDER BY b.title ASC";
        $stmt = $conn->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $books = [];
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }

        // single query to get all currently lent book IDs
        $lent_ids = [];
        $lr = $conn->query("SELECT book_id FROM lendings WHERE returned_date IS NULL");
        while ($lrow = $lr->fetch_assoc()) {
            $lent_ids[$lrow['book_id']] = true;
        }
        foreach ($books as &$b) {
            $b['is_lent'] = isset($lent_ids[$b['id']]);
        }
        unset($b);
        $stmt->close();
        echo json_encode(['books' => $books]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $title       = trim($data['title']          ?? '');
        $author      = trim($data['author']         ?? '');
        $pages       = isset($data['pages']) && $data['pages'] !== '' ? (int)$data['pages'] : null;
        $genre_id    = isset($data['genre_id']) && $data['genre_id'] !== '' ? (int)$data['genre_id'] : null;
        $isbn        = trim($data['isbn']            ?? '');
        $pub_year    = isset($data['published_year']) && $data['published_year'] !== '' ? (int)$data['published_year'] : null;
        $description = trim($data['description']    ?? '');
        $cover_color = trim($data['cover_color']    ?? '#4a6fa5');

        if (!$title || !$author) {
            echo json_encode(['error' => 'Title and author are required.']);
            break;
        }

        $stmt = $conn->prepare("INSERT INTO books (title, author, pages, genre_id, isbn, published_year, description, cover_color) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssiissss", $title, $author, $pages, $genre_id, $isbn, $pub_year, $description, $cover_color);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['error' => 'Insert failed: ' . $conn->error]);
        }
        $stmt->close();
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $id          = (int)($data['id']             ?? 0);
        $title       = trim($data['title']           ?? '');
        $author      = trim($data['author']          ?? '');
        $pages       = isset($data['pages']) && $data['pages'] !== '' ? (int)$data['pages'] : null;
        $genre_id    = isset($data['genre_id']) && $data['genre_id'] !== '' ? (int)$data['genre_id'] : null;
        $isbn        = trim($data['isbn']            ?? '');
        $pub_year    = isset($data['published_year']) && $data['published_year'] !== '' ? (int)$data['published_year'] : null;
        $description = trim($data['description']    ?? '');
        $cover_color = trim($data['cover_color']    ?? '#4a6fa5');

        if (!$id || !$title || !$author) {
            echo json_encode(['error' => 'ID, title, and author are required.']);
            break;
        }

        $stmt = $conn->prepare("UPDATE books SET title=?, author=?, pages=?, genre_id=?, isbn=?, published_year=?, description=?, cover_color=? WHERE id=?");
        $stmt->bind_param("ssiissssi", $title, $author, $pages, $genre_id, $isbn, $pub_year, $description, $cover_color, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Update failed: ' . $conn->error]);
        }
        $stmt->close();
        break;

    case 'DELETE':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'Invalid ID.']); break; }
        $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Delete failed.']);
        }
        $stmt->close();
        break;
}

$conn->close();
?>
