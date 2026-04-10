<?php
require_once __DIR__ . '/../config/database.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) die('Invalid ID');

$sql = "
SELECT 
    s.*,
    ar.artist_name,
    al.album_name,
    g.genre_name
FROM songs s
JOIN artists ar ON ar.artist_id = s.artist_id
LEFT JOIN albums al ON al.album_id = s.album_id
LEFT JOIN genres g ON g.genre_id = s.genre_id
WHERE s.song_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$song = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$song) die('Song not found');

/* ======================
   YOUTUBE LOGIC
====================== */
$hasYoutubeId = !empty($song['youtube_video_id']);

$youtubeSearchUrl =
    'https://www.youtube.com/results?search_query=' .
    urlencode($song['title'] . ' ' . $song['artist_name']);
?>

<div id="content-wrapper" class="d-flex flex-column">
<div id="content">
<?php include 'includes/topbar.php'; ?>

<div class="container-fluid">

<h1 class="h3 mb-4 text-gray-800">Chi tiết bài hát</h1>

<div class="card shadow mb-4">
<div class="card-body">

<div class="row mb-3">
    <div class="col-md-3 font-weight-bold">Tiêu đề</div>
    <div class="col-md-9"><?= htmlspecialchars($song['title']) ?></div>
</div>

<div class="row mb-3">
    <div class="col-md-3 font-weight-bold">Nghệ sĩ</div>
    <div class="col-md-9"><?= htmlspecialchars($song['artist_name']) ?></div>
</div>

<div class="row mb-3">
    <div class="col-md-3 font-weight-bold">Album</div>
    <div class="col-md-9"><?= htmlspecialchars($song['album_name'] ?? '—') ?></div>
</div>

<div class="row mb-3">
    <div class="col-md-3 font-weight-bold">Thể loại</div>
    <div class="col-md-9"><?= htmlspecialchars($song['genre_name'] ?? '—') ?></div>
</div>

<div class="row mb-3">
    <div class="col-md-3 font-weight-bold">Ngày phát hành</div>
    <div class="col-md-9"><?= htmlspecialchars($song['release_date']) ?></div>
</div>

<div class="row mb-4">
    <div class="col-md-3 font-weight-bold">Ngôn ngữ</div>
    <div class="col-md-9"><?= htmlspecialchars($song['language']) ?></div>
</div>

<hr>

<h5>🎬 YouTube Preview</h5>

<?php if ($hasYoutubeId): ?>
    <div class="embed-responsive embed-responsive-16by9 mb-4">
        <iframe
            class="embed-responsive-item"
            src="https://www.youtube.com/embed/<?= htmlspecialchars($song['youtube_video_id']) ?>"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen>
        </iframe>
    </div>
<?php else: ?>
    <a href="<?= $youtubeSearchUrl ?>" target="_blank" class="btn btn-danger mb-4">
        🔗 Mở trên YouTube
    </a>
<?php endif; ?>

<h5>Lyrics</h5>
<pre style="white-space:pre-wrap"><?= htmlspecialchars($song['lyrics'] ?? 'Chưa có lyrics') ?></pre>

<h5 class="mt-4">Meaning</h5>
<pre style="white-space:pre-wrap"><?= htmlspecialchars($song['meaning'] ?? 'Chưa có meaning') ?></pre>

<hr>

<div class="mt-3">
    <a href="songs.php" class="btn btn-secondary">← Quay lại</a>

    <a href="edit_song.php?id=<?= $song['song_id'] ?>" class="btn btn-warning">
        Sửa
    </a>

    <a href="delete_song.php?id=<?= $song['song_id'] ?>"
       onclick="return confirm('Bạn chắc chắn muốn xóa bài hát này?')"
       class="btn btn-danger">
        Xóa
    </a>
</div>

</div>
</div>

</div>
</div>

<?php
include 'includes/footer.php';
include 'includes/scripts.php';
