<?php
include('database.php');
require_once __DIR__ . '/../../config/song_helpers.php';

header('Content-Type: application/json; charset=utf-8');

function nln_search_label($text)
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

$q = trim($_GET['q'] ?? '');
if ($q === '') {
    echo json_encode([
        'items' => [],
        'related_keywords' => []
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$term = '%' . $q . '%';
$prefix = $q . '%';

$sql = "
    SELECT
        s.song_id,
        s.title,
        a.artist_name,
        al.album_name,
        g.genre_name,
        s.release_date,
        s.cover_image AS song_cover,
        al.cover_image AS album_cover,
        COUNT(sl.log_id) AS search_count,
        CASE
            WHEN LOWER(s.title) = LOWER(?) THEN 500
            WHEN LOWER(a.artist_name) = LOWER(?) THEN 420
            WHEN s.title LIKE ? THEN 260
            WHEN a.artist_name LIKE ? THEN 220
            WHEN al.album_name LIKE ? THEN 180
            WHEN g.genre_name LIKE ? THEN 140
            ELSE 80
        END AS priority
    FROM songs s
    JOIN artists a ON a.artist_id = s.artist_id
    LEFT JOIN albums al ON al.album_id = s.album_id
    LEFT JOIN genres g ON g.genre_id = s.genre_id
    LEFT JOIN search_logs sl ON sl.song_id = s.song_id
    WHERE
        s.title LIKE ?
        OR a.artist_name LIKE ?
        OR al.album_name LIKE ?
        OR g.genre_name LIKE ?
    GROUP BY
        s.song_id,
        s.title,
        a.artist_name,
        al.album_name,
        g.genre_name,
        s.release_date,
        s.cover_image,
        al.cover_image
    ORDER BY priority DESC, search_count DESC, s.release_date DESC, s.title ASC
    LIMIT 8
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    'ssssssssss',
    $q,
    $q,
    $prefix,
    $prefix,
    $prefix,
    $prefix,
    $term,
    $term,
    $term,
    $term
);
$stmt->execute();
$res = $stmt->get_result();

$items = [];
$keywordPool = [];

while ($row = $res->fetch_assoc()) {
    $cover = nln_public_song_cover_path($row['song_cover'] ?? null, $row['album_cover'] ?? null);

    foreach (['artist_name', 'album_name', 'genre_name'] as $field) {
        if (!empty($row[$field]) && mb_strtolower($row[$field]) !== mb_strtolower($q)) {
            $keywordPool[mb_strtolower($row[$field])] = $row[$field];
        }
    }

    $subtitleParts = [nln_search_label($row['artist_name'])];
    if (!empty($row['album_name'])) {
        $subtitleParts[] = nln_search_label($row['album_name']);
    }

    $items[] = [
        'song_id' => (int) $row['song_id'],
        'title' => nln_search_label($row['title']),
        'artist_name' => nln_search_label($row['artist_name']),
        'album_name' => nln_search_label($row['album_name'] ?? ''),
        'genre_name' => nln_search_label($row['genre_name'] ?? ''),
        'release_date' => $row['release_date'],
        'cover' => $cover,
        'search_count' => (int) $row['search_count'],
        'subtitle' => implode(' • ', array_filter($subtitleParts))
    ];
}

$stmt->close();

$relatedKeywords = array_slice(array_values($keywordPool), 0, 6);

echo json_encode([
    'items' => $items,
    'related_keywords' => array_values($relatedKeywords)
], JSON_UNESCAPED_UNICODE);
