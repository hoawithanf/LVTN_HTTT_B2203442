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
$notification_id = (int)($_POST['notification_id'] ?? 0);

if ($notification_id > 0) {
    $stmt = $conn->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE notification_id = ?
          AND user_id = ?
    ");
    $stmt->bind_param("ii", $notification_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

echo json_encode(['success' => true]);
