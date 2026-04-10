<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/upload_helpers.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$song_id = (int) ($_GET['id'] ?? 0);
if ($song_id <= 0) {
    header("Location: songs.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $artist_id = (int) $_POST['artist_id'];
    $genre_id = (int) $_POST['genre_id'];
    $album_id = !empty($_POST['album_id']) ? (int) $_POST['album_id'] : null;
    $release_date = $_POST['release_date'] ?: null;
    $language = $_POST['language'] ?: null;
    $lyrics = $_POST['lyrics'] ?: null;
    $meaning = $_POST['meaning'] ?: null;

    $coverImage = $_POST['old_cover'];

    if (!empty($_FILES['cover_image']['name'])) {
        $upload = nln_upload_image(
            $_FILES['cover_image'],
            __DIR__ . '/../public/assets/img/albums',
            'song'
        );

        if (!$upload['success']) {
            $error = $upload['error'];
        } else {
            $coverImage = $upload['filename'];
        }
    }

    if ($error === '') {
        $stmt = $conn->prepare("
            UPDATE songs SET
                title=?,
                artist_id=?,
                genre_id=?,
                album_id=?,
                cover_image=?,
                release_date=?,
                language=?,
                lyrics=?,
                meaning=?
            WHERE song_id=?
        ");
        $stmt->bind_param("siiisssssi", $title, $artist_id, $genre_id, $album_id, $coverImage, $release_date, $language, $lyrics, $meaning, $song_id);
        $stmt->execute();
        $stmt->close();

        header("Location: songs.php");
        exit;
    }
}

$songStmt = $conn->prepare("SELECT * FROM songs WHERE song_id=?");
$songStmt->bind_param("i", $song_id);
$songStmt->execute();
$song = $songStmt->get_result()->fetch_assoc();
$songStmt->close();

if (!$song) {
    header("Location: songs.php");
    exit;
}

$artists = $conn->query("SELECT artist_id, artist_name FROM artists ORDER BY artist_name");
$genres = $conn->query("SELECT genre_id, genre_name FROM genres ORDER BY genre_name");
$albums = $conn->query("SELECT album_id, album_name FROM albums WHERE artist_id=" . (int) $song['artist_id']);
?>

<div id="content-wrapper" class="d-flex flex-column">
<div id="content">
<?php include 'includes/topbar.php'; ?>

<div class="container-fluid">
<h1 class="h3 mb-4 text-gray-800">Sửa bài hát</h1>

<?php if ($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">

<input type="hidden" name="old_cover" value="<?= htmlspecialchars($song['cover_image']) ?>">

<div class="form-group">
    <label>Tiêu đề</label>
    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($song['title']) ?>" required>
</div>

<div class="form-group">
    <label>Nghệ sĩ</label>
    <select name="artist_id" id="artistSelect" class="form-control" required>
        <?php while ($a = $artists->fetch_assoc()): ?>
            <option value="<?= $a['artist_id'] ?>" <?= $a['artist_id'] == $song['artist_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($a['artist_name']) ?>
            </option>
        <?php endwhile; ?>
    </select>
</div>

<div class="form-group">
    <label>Thể loại</label>
    <select name="genre_id" class="form-control" required>
        <?php while ($g = $genres->fetch_assoc()): ?>
            <option value="<?= $g['genre_id'] ?>" <?= $g['genre_id'] == $song['genre_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($g['genre_name']) ?>
            </option>
        <?php endwhile; ?>
    </select>
</div>

<div class="form-group">
    <label>Album</label>
    <select name="album_id" id="albumSelect" class="form-control">
        <option value="">-- Không --</option>
        <?php while ($al = $albums->fetch_assoc()): ?>
            <option value="<?= $al['album_id'] ?>" <?= $al['album_id'] == $song['album_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($al['album_name']) ?>
            </option>
        <?php endwhile; ?>
    </select>
</div>

<div class="form-group">
    <label>Ảnh cover</label>
    <input type="file" name="cover_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
    <small class="form-text text-muted">Cho phép JPG, PNG, WEBP. Tối đa 5MB.</small>
</div>

<div class="form-group">
    <label>Ngày phát hành</label>
    <input type="date" name="release_date" value="<?= htmlspecialchars($song['release_date']) ?>" class="form-control">
</div>

<div class="form-group">
    <label>Ngôn ngữ</label>
    <input type="text" name="language" value="<?= htmlspecialchars($song['language']) ?>" class="form-control">
</div>

<div class="form-group">
    <label>Lyrics</label>
    <textarea name="lyrics" class="form-control" rows="6"><?= htmlspecialchars($song['lyrics']) ?></textarea>
</div>

<div class="form-group">
    <label>Meaning</label>
    <textarea name="meaning" class="form-control" rows="6"><?= htmlspecialchars($song['meaning']) ?></textarea>
</div>

<button class="btn btn-success">Cập nhật</button>
<a href="songs.php" class="btn btn-secondary">Hủy</a>
</form>
</div>
</div>

<?php include 'includes/footer.php'; ?>
<?php include 'includes/scripts.php'; ?>

<script>
document.getElementById('artistSelect').addEventListener('change', function () {
    const artistId = this.value;
    fetch('ajax_get_albums.php?artist_id=' + artistId)
        .then(res => res.json())
        .then(data => {
            const select = document.getElementById('albumSelect');
            select.innerHTML = '<option value="">-- Không --</option>';
            data.forEach(a => {
                select.innerHTML += `<option value="${a.album_id}">${a.album_name}</option>`;
            });
        });
});
</script>
