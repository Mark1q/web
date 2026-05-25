<?php
header('Content-Type: application/json');
require_once '../db.php';

$total   = $conn->query("SELECT COUNT(*) as c FROM books")->fetch_assoc()['c'];
$lent    = $conn->query("SELECT COUNT(*) as c FROM lendings WHERE returned_date IS NULL")->fetch_assoc()['c'];
$genres  = $conn->query("SELECT COUNT(*) as c FROM genres")->fetch_assoc()['c'];
$overdue = $conn->query("SELECT COUNT(*) as c FROM lendings WHERE returned_date IS NULL AND due_date < CURDATE()")->fetch_assoc()['c'];

echo json_encode([
    'total_books' => (int)$total,
    'lent_out'    => (int)$lent,
    'genres'      => (int)$genres,
    'overdue'     => (int)$overdue,
]);
$conn->close();
?>