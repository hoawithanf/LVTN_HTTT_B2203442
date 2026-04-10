<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=UTF-8');

include_once __DIR__ . "/env_loader.php";
loadEnv(__DIR__ . "/../../.env");

include('database.php');
include('lyrics_api.php');

$song_id = isset($_GET['song_id']) ? (int) $_GET['song_id'] : 0;

if (!$song_id) {
    echo json_encode(['success' => false, 'error' => 'Thiếu song_id']);
    exit;
}

$q = $conn->prepare("
    SELECT s.song_id, s.title, a.artist_name
    FROM songs s
    JOIN artists a ON s.artist_id = a.artist_id
    WHERE s.song_id = ?
");
$q->bind_param("i", $song_id);
$q->execute();
$res = $q->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Không tìm thấy bài hát']);
    exit;
}

$song = $res->fetch_assoc();

$r = fetchLyricsAndCache($conn, $song['artist_name'], $song['title'], $song_id);

echo json_encode($r);
