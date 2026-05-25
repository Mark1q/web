<?php
header('Content-Type: application/json');
require_once '../db.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $active_only = isset($_GET['active']) && $_GET['active'] === '1';
        $sql = "SELECT l.*, b.title, b.author, b.cover_color FROM lendings l JOIN books b ON l.book_id = b.id";
        if ($active_only) $sql .= " WHERE l.returned_date IS NULL";
        $sql .= " ORDER BY l.lent_date DESC";
        $result = $conn->query($sql);
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        echo json_encode(['lendings' => $rows]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $book_id   = (int)($data['book_id']          ?? 0);
        $borrower  = trim($data['borrower_name']     ?? '');
        $contact   = trim($data['borrower_contact']  ?? '');
        $lent_date = trim($data['lent_date']         ?? '');
        $due_date  = trim($data['due_date']          ?? '');
        $notes     = trim($data['notes']             ?? '');

        if (!$book_id || !$borrower || !$lent_date) {
            echo json_encode(['error' => 'Book, borrower, and lent date are required.']);
            break;
        }

        // Check not already lent
        $chk = $conn->prepare("SELECT id FROM lendings WHERE book_id = ? AND returned_date IS NULL");
        $chk->bind_param("i", $book_id);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            echo json_encode(['error' => 'This book is already lent out.']);
            $chk->close(); break;
        }
        $chk->close();

        $due = $due_date ?: null;
        $stmt = $conn->prepare("INSERT INTO lendings (book_id, borrower_name, borrower_contact, lent_date, due_date, notes) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("isssss", $book_id, $borrower, $contact, $lent_date, $due, $notes);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['error' => 'Insert failed: ' . $conn->error]);
        }
        $stmt->close();
        break;

    case 'PUT':
        // Mark as returned
        $data    = json_decode(file_get_contents('php://input'), true);
        $id      = (int)($data['id'] ?? 0);
        $ret_date = trim($data['returned_date'] ?? date('Y-m-d'));
        if (!$id) { echo json_encode(['error' => 'Invalid ID.']); break; }
        $stmt = $conn->prepare("UPDATE lendings SET returned_date=? WHERE id=?");
        $stmt->bind_param("si", $ret_date, $id);
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
        $stmt = $conn->prepare("DELETE FROM lendings WHERE id=?");
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
