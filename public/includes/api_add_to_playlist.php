<?php
header("Content-Type: application/json; charset=UTF-8");
include("session.php");
include("database.php");
require_once __DIR__ . '/../../config/playlist_helpers.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'not_logged_in']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$songId = (int) ($_POST['song_id'] ?? 0);
$playlistId = isset($_POST['playlist_id']) && $_POST['playlist_id'] !== '' ? (int) $_POST['playlist_id'] : 0;
$playlistName = trim((string) ($_POST['playlist_name'] ?? ''));

if ($songId <= 0) {
    echo json_encode(['success' => false, 'error' => 'invalid_song']);
    exit;
}

$songStmt = $conn->prepare("SELECT song_id FROM songs WHERE song_id = ? LIMIT 1");
$songStmt->bind_param('i', $songId);
$songStmt->execute();
$songExists = $songStmt->get_result()->num_rows > 0;
$songStmt->close();

if (!$songExists) {
    echo json_encode(['success' => false, 'error' => 'song_not_found']);
    exit;
}

nln_playlist_ensure_schema($conn);

$playlist = null;
if ($playlistId > 0) {
    $playlist = nln_playlist_find_user_playlist($conn, $userId, $playlistId);
} elseif ($playlistName !== '') {
    $playlist = nln_playlist_create($conn, $userId, $playlistName);
}

if (!$playlist) {
    echo json_encode(['success' => false, 'error' => 'playlist_not_found']);
    exit;
}

$result = nln_playlist_add_song($conn, (int) $playlist['playlist_id'], $songId);
if (!$result['success']) {
    echo json_encode([
        'success' => false,
        'error' => 'add_failed',
        'message' => $result['error'] ?? '',
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'playlist_id' => (int) $playlist['playlist_id'],
    'playlist_name' => (string) $playlist['playlist_name'],
]);
