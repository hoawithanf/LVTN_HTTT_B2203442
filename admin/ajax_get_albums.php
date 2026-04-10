<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$artist_id = isset($_GET['artist_id']) ? (int)$_GET['artist_id'] : 0;

if ($artist_id <= 0) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT album_id, album_name
    FROM albums
    WHERE artist_id = ?
    ORDER BY release_year DESC, album_name
");
$stmt->bind_param("i", $artist_id);
$stmt->execute();
$res = $stmt->get_result();

$albums = [];
while ($row = $res->fetch_assoc()) {
    $albums[] = $row;
}

echo json_encode($albums);
