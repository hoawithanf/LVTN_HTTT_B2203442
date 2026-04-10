<?php
header("Content-Type: application/json; charset=UTF-8");
include("session.php");
include("database.php");
require_once __DIR__ . '/../../config/comment_helpers.php';

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "error" => "not_logged_in"]);
    exit;
}

$userId = (int) $_SESSION["user_id"];
$commentId = (int) ($_POST["comment_id"] ?? 0);
$content = trim((string) ($_POST["comment"] ?? ""));

if ($commentId <= 0 || $content === "") {
    echo json_encode(["success" => false, "error" => "invalid_payload"]);
    exit;
}

nln_comment_ensure_schema($conn);

$ownerStmt = $conn->prepare("SELECT user_id FROM comments WHERE comment_id = ? LIMIT 1");
$ownerStmt->bind_param("i", $commentId);
$ownerStmt->execute();
$owner = $ownerStmt->get_result()->fetch_assoc();
$ownerStmt->close();

if (!$owner || (int) $owner['user_id'] !== $userId) {
    echo json_encode(["success" => false, "error" => "forbidden"]);
    exit;
}

$stmt = $conn->prepare("UPDATE comments SET content = ?, updated_at = NOW() WHERE comment_id = ?");
$stmt->bind_param("si", $content, $commentId);
$stmt->execute();
$stmt->close();

echo json_encode(["success" => true]);
