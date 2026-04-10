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

if ($commentId <= 0) {
    echo json_encode(["success" => false, "error" => "invalid_comment"]);
    exit;
}

nln_comment_ensure_schema($conn);

$existsStmt = $conn->prepare("SELECT 1 FROM comments WHERE comment_id = ? LIMIT 1");
$existsStmt->bind_param("i", $commentId);
$existsStmt->execute();
$exists = $existsStmt->get_result()->num_rows > 0;
$existsStmt->close();

if (!$exists) {
    echo json_encode(["success" => false, "error" => "comment_not_found"]);
    exit;
}

$likeStmt = $conn->prepare("SELECT like_id FROM comment_likes WHERE comment_id = ? AND user_id = ? LIMIT 1");
$likeStmt->bind_param("ii", $commentId, $userId);
$likeStmt->execute();
$liked = $likeStmt->get_result()->fetch_assoc();
$likeStmt->close();

if ($liked) {
    $deleteStmt = $conn->prepare("DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?");
    $deleteStmt->bind_param("ii", $commentId, $userId);
    $deleteStmt->execute();
    $deleteStmt->close();
    $isLiked = false;
} else {
    $insertStmt = $conn->prepare("INSERT INTO comment_likes (comment_id, user_id) VALUES (?, ?)");
    $insertStmt->bind_param("ii", $commentId, $userId);
    $insertStmt->execute();
    $insertStmt->close();
    $isLiked = true;
}

$countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM comment_likes WHERE comment_id = ?");
$countStmt->bind_param("i", $commentId);
$countStmt->execute();
$count = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
$countStmt->close();

echo json_encode([
    "success" => true,
    "liked" => $isLiked,
    "like_count" => $count,
]);
