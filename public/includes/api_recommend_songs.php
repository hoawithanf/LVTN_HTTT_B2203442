<?php
header("Content-Type: application/json; charset=UTF-8");
include("database.php");
require_once __DIR__ . '/../../config/song_helpers.php';

$user_id = isset($_GET["user_id"]) ? (int)$_GET["user_id"] : 0;
$song_id = isset($_GET["song_id"]) ? (int)$_GET["song_id"] : 0;

if ($user_id <= 0) {
    echo json_encode(["success" => false, "error" => "Missing user_id"]);
    exit;
}

$response = [
    "success" => true,
    "top_favorites" => [],
    "same_artist" => [],
    "same_album" => [],
    "collaborative" => []
];

/* ============================================================
   1) Top bài hát user xem / tìm nhiều nhất
   ============================================================ */
$q = $conn->prepare("
    SELECT s.song_id, s.title, s.cover_image AS song_cover,
           al.cover_image AS album_cover,
           a.artist_name, s.artist_id
    FROM search_logs l
    JOIN songs s ON s.song_id = l.song_id
    JOIN artists a ON a.artist_id = s.artist_id
    LEFT JOIN albums al ON al.album_id = s.album_id
    WHERE l.user_id = ?
    GROUP BY s.song_id
    ORDER BY COUNT(*) DESC
    LIMIT 5
");
$q->bind_param("i", $user_id);
$q->execute();
$topFavorites = $q->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($topFavorites as &$item) {
    $item['cover'] = nln_public_song_cover_path($item['song_cover'] ?? null, $item['album_cover'] ?? null);
    $item['cover_image'] = $item['cover'];
}
unset($item);
$response["top_favorites"] = $topFavorites;

/* ============================================================
   2) Gợi ý bài hát cùng ca sĩ user yêu thích nhất
   ============================================================ */
$q2 = $conn->prepare("
    SELECT a.artist_id, a.artist_name, COUNT(*) AS total
    FROM search_logs l
    JOIN songs s ON s.song_id = l.song_id
    JOIN artists a ON a.artist_id = s.artist_id
    WHERE l.user_id = ?
    GROUP BY a.artist_id
    ORDER BY total DESC
    LIMIT 1
");
$q2->bind_param("i", $user_id);
$q2->execute();
$top_artist = $q2->get_result()->fetch_assoc();

if ($top_artist) {
    $artist_id = (int)$top_artist["artist_id"];

    $q3 = $conn->prepare("
        SELECT s.song_id, s.title, s.cover_image AS song_cover, al.cover_image AS album_cover, a.artist_name
        FROM songs s
        JOIN artists a ON a.artist_id = s.artist_id
        LEFT JOIN albums al ON al.album_id = s.album_id
        WHERE s.artist_id = ?
        ORDER BY RAND()
        LIMIT 8
    ");
    $q3->bind_param("i", $artist_id);
    $q3->execute();
    $sameArtist = $q3->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($sameArtist as &$item) {
        $item['cover'] = nln_public_song_cover_path($item['song_cover'] ?? null, $item['album_cover'] ?? null);
        $item['cover_image'] = $item['cover'];
    }
    unset($item);
    $response["same_artist"] = $sameArtist;
}

/* ============================================================
   3) Gợi ý bài hát cùng album user hay nghe
   ============================================================ */
$q4 = $conn->prepare("
    SELECT s.album_id, COUNT(*) AS total
    FROM search_logs l
    JOIN songs s ON s.song_id = l.song_id
    WHERE l.user_id = ? AND s.album_id IS NOT NULL
    GROUP BY s.album_id
    ORDER BY total DESC
    LIMIT 1
");
$q4->bind_param("i", $user_id);
$q4->execute();
$top_album = $q4->get_result()->fetch_assoc();

if ($top_album) {
    $album_id = (int)$top_album["album_id"];

    $q5 = $conn->prepare("
        SELECT s.song_id, s.title, s.cover_image AS song_cover, al.cover_image AS album_cover
        FROM songs
        LEFT JOIN albums al ON al.album_id = songs.album_id
        WHERE album_id = ?
        ORDER BY RAND()
        LIMIT 8
    ");
    $q5->bind_param("i", $album_id);
    $q5->execute();
    $sameAlbum = $q5->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($sameAlbum as &$item) {
        $item['cover'] = nln_public_song_cover_path($item['song_cover'] ?? null, $item['album_cover'] ?? null);
        $item['cover_image'] = $item['cover'];
    }
    unset($item);
    $response["same_album"] = $sameAlbum;
}

/* ============================================================
   4) Collaborative Filtering
      (người giống bạn cũng nghe bài gì)
   ============================================================ */
$q6 = $conn->prepare("
    SELECT DISTINCT user_id
    FROM search_logs
    WHERE song_id IN (
        SELECT song_id FROM search_logs WHERE user_id = ?
    ) AND user_id != ?
");
$q6->bind_param("ii", $user_id, $user_id);
$q6->execute();
$user_list = array_column($q6->get_result()->fetch_all(MYSQLI_ASSOC), 'user_id');

if (!empty($user_list)) {

    $in = implode(",", array_map("intval", $user_list));

    $sql = "
        SELECT s.song_id, s.title, s.cover_image AS song_cover, al.cover_image AS album_cover, a.artist_name,
               COUNT(*) AS freq
        FROM search_logs l
        JOIN songs s ON s.song_id = l.song_id
        JOIN artists a ON a.artist_id = s.artist_id
        LEFT JOIN albums al ON al.album_id = s.album_id
        WHERE l.user_id IN ($in)
        GROUP BY s.song_id
        ORDER BY freq DESC
        LIMIT 10
    ";

    $collaborative = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    foreach ($collaborative as &$item) {
        $item['cover'] = nln_public_song_cover_path($item['song_cover'] ?? null, $item['album_cover'] ?? null);
        $item['cover_image'] = $item['cover'];
    }
    unset($item);
    $response["collaborative"] = $collaborative;
}

/* ============================================================
   Nếu tất cả rỗng → tránh lỗi JS
   ============================================================ */
if (
    empty($response["top_favorites"]) &&
    empty($response["same_artist"]) &&
    empty($response["same_album"]) &&
    empty($response["collaborative"])
) {
    $response["message"] = "No data";
}

echo json_encode($response);
?>
