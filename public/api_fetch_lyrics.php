<?php
header('Content-Type: application/json; charset=utf-8');
include('includes/database.php');
include('includes/lyrics_api.php');

$artist = $_GET['artist'] ?? '';
$title  = $_GET['title']  ?? '';

if ($artist === '' || $title === '') {
    echo json_encode(['success' => false, 'error' => 'Thiếu artist hoặc title']);
    exit;
}

$r = fetchLyricsAndCache($conn, $artist, $title);
echo json_encode($r);
