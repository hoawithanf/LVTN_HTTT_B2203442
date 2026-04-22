<?php
header('Content-Type: application/json; charset=UTF-8');

include_once __DIR__ . '/env_loader.php';
loadEnv(__DIR__ . '/../../.env');

include('database.php');
include('lyrics_api.php');
require_once __DIR__ . '/lyrics_translation_api.php';

$song_id = isset($_GET['song_id']) ? (int) $_GET['song_id'] : 0;

if ($song_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Thiếu song_id']);
    exit;
}

nln_lyrics_translation_ensure_schema($conn);

$stmt = $conn->prepare("
    SELECT s.song_id, s.title, s.lyrics, s.lyrics_vi, a.artist_name
    FROM songs s
    JOIN artists a ON s.artist_id = a.artist_id
    WHERE s.song_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $song_id);
$stmt->execute();
$result = $stmt->get_result();
$song = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$song) {
    echo json_encode(['success' => false, 'error' => 'Không tìm thấy bài hát']);
    exit;
}

$cachedTranslation = trim((string) ($song['lyrics_vi'] ?? ''));
if ($cachedTranslation !== '') {
    echo json_encode([
        'success' => true,
        'cached' => true,
        'lyrics_vi' => $cachedTranslation,
    ]);
    exit;
}

$lyrics = trim((string) ($song['lyrics'] ?? ''));
if ($lyrics === '') {
    $lyricsResponse = fetchLyricsAndCache($conn, (string) $song['artist_name'], (string) $song['title'], $song_id);
    if (empty($lyricsResponse['success']) || empty($lyricsResponse['lyrics'])) {
        echo json_encode([
            'success' => false,
            'error' => $lyricsResponse['error'] ?? 'Chưa có lyrics gốc để dịch.',
        ]);
        exit;
    }
    $lyrics = trim((string) $lyricsResponse['lyrics']);
}

$translation = nln_translate_lyrics_to_vietnamese($lyrics, (string) $song['title'], (string) $song['artist_name']);
if (empty($translation['success']) || empty($translation['lyrics_vi'])) {
    echo json_encode([
        'success' => false,
        'error' => $translation['error'] ?? 'Chưa thể dịch lyrics lúc này.',
    ]);
    exit;
}

nln_lyrics_translation_save_cache($conn, $song_id, (string) $translation['lyrics_vi']);

echo json_encode([
    'success' => true,
    'cached' => false,
    'lyrics_vi' => (string) $translation['lyrics_vi'],
    'model' => $translation['model'] ?? null,
]);
