<?php
header("Content-Type: application/json; charset=UTF-8");
include("session.php");
include("database.php");
require_once __DIR__ . '/../../config/comment_helpers.php';

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "error" => "not_logged_in"]);
    exit;
}

$user_id = $_SESSION["user_id"];
$song_id = (int)($_POST["song_id"] ?? 0);
$content = trim($_POST["comment"] ?? "");
$parent_comment_id = isset($_POST["parent_comment_id"]) && $_POST["parent_comment_id"] !== ""
    ? (int) $_POST["parent_comment_id"]
    : null;

if ($song_id <= 0 || $content == "") {
    echo json_encode(["success" => false, "error" => "invalid_input"]);
    exit;
}

nln_comment_ensure_schema($conn);

if ($parent_comment_id !== null) {
    $parentStmt = $conn->prepare("SELECT comment_id FROM comments WHERE comment_id = ? AND song_id = ? LIMIT 1");
    $parentStmt->bind_param("ii", $parent_comment_id, $song_id);
    $parentStmt->execute();
    $parentExists = $parentStmt->get_result()->num_rows > 0;
    $parentStmt->close();

    if (!$parentExists) {
        echo json_encode(["success" => false, "error" => "parent_not_found"]);
        exit;
    }
}

$q = $conn->prepare("
    INSERT INTO comments (song_id, user_id, parent_comment_id, content, created_at, updated_at)
    VALUES (?, ?, ?, ?, NOW(), NOW())
");

if (!$q) {
    echo json_encode(["success" => false, "error" => "prepare_failed"]);
    exit;
}

$q->bind_param("iiis", $song_id, $user_id, $parent_comment_id, $content);
$ok = $q->execute();

if (!$ok) {
    echo json_encode([
        "success" => false,
        "error" => "insert_failed",
        "message" => $q->error,
    ]);
    $q->close();
    exit;
}

$insertId = (int) $conn->insert_id;
$q->close();

echo json_encode([
    "success" => true,
    "comment_id" => $insertId,
]);
