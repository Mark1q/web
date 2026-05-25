<?php
header('Content-Type: application/json');
require_once '../db.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $result = $conn->query("SELECT g.*, COUNT(b.id) as book_count FROM genres g LEFT JOIN books b ON g.id = b.genre_id GROUP BY g.id ORDER BY g.name");
        $genres = [];
        while ($row = $result->fetch_assoc()) $genres[] = $row;
        echo json_encode(['genres' => $genres]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $name = trim($data['name'] ?? '');
        if (!$name) { echo json_encode(['error' => 'Name required.']); break; }
        $stmt = $conn->prepare("INSERT INTO genres (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['error' => 'Duplicate or error: ' . $conn->error]);
        }
        $stmt->close();
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $id   = (int)($data['id']   ?? 0);
        $name = trim($data['name'] ?? '');
        if (!$id || !$name) { echo json_encode(['error' => 'ID and name required.']); break; }
        $stmt = $conn->prepare("UPDATE genres SET name=? WHERE id=?");
        $stmt->bind_param("si", $name, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Update failed.']);
        }
        $stmt->close();
        break;

    case 'DELETE':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'Invalid ID.']); break; }
        // Check books using this genre
        $chk = $conn->prepare("SELECT COUNT(*) as c FROM books WHERE genre_id = ?");
        $chk->bind_param("i", $id);
        $chk->execute();
        $cnt = $chk->get_result()->fetch_assoc()['c'];
        $chk->close();
        if ($cnt > 0) {
            echo json_encode(['error' => "Cannot delete: $cnt book(s) use this genre."]);
            break;
        }
        $stmt = $conn->prepare("DELETE FROM genres WHERE id=?");
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
