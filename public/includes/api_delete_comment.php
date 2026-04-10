<?php
header("Content-Type: application/json; charset=UTF-8");
include("session.php");
include("database.php");
require_once __DIR__ . '/../../config/comment_helpers.php';

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false]);
    exit;
}

$user_id = $_SESSION["user_id"];
$comment_id = (int)($_POST["comment_id"] ?? 0);

// Lấy chủ sở hữu bình luận
$q = $conn->prepare("SELECT user_id FROM comments WHERE comment_id = ?");
$q->bind_param("i", $comment_id);
$q->execute();
$owner = $q->get_result()->fetch_assoc();

if (!$owner) {
    echo json_encode(["success" => false]);
    exit;
}

// Chỉ chủ comment hoặc admin user_id = 1 mới được xoá
if ($owner["user_id"] != $user_id && $user_id != 1) {
    echo json_encode(["success" => false]);
    exit;
}

nln_comment_delete($conn, $comment_id);

echo json_encode(["success" => true]);
