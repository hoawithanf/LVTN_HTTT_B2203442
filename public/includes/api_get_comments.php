<?php
header("Content-Type: application/json; charset=UTF-8");
include("session.php");
include("database.php");
require_once __DIR__ . '/../../config/comment_helpers.php';

$song_id = isset($_GET["song_id"]) ? (int)$_GET["song_id"] : 0;
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$adminDelete = $currentUserId === 1;
$comments = nln_comment_fetch_tree($conn, $song_id, $currentUserId, $adminDelete);

echo json_encode([
    "success" => true,
    "comments" => $comments
]);
