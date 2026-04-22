<?php
include_once('session.php');
include_once('database.php');

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'UNAUTHORIZED']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("
    DELETE FROM notifications
    WHERE user_id = ?
      AND is_read = 1
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'PREPARE_FAILED']);
    exit;
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$deletedRows = $stmt->affected_rows;
$stmt->close();

echo json_encode([
    'success' => true,
    'deleted' => $deletedRows,
]);
