<?php
include('../includes/database.php');

$album_id = isset($_GET['album_id']) ? (int) $_GET['album_id'] : 0;

$q = $conn->prepare("
    SELECT song_id, title
    FROM songs
    WHERE album_id = ?
");
$q->bind_param("i", $album_id);
$q->execute();
$result = $q->get_result();

echo "<h4 class='mt-4'>Bai hat trong album</h4>";

while ($s = $result->fetch_assoc()) {
    $songId = (int) $s['song_id'];
    $songTitle = htmlspecialchars((string) $s['title'], ENT_QUOTES, 'UTF-8');
    echo "<p><a href='post.php?id={$songId}'>{$songTitle}</a></p>";
}
