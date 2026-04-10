<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/upload_helpers.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $artist_id = (int) $_POST['artist_id'];
    $genre_id = (int) $_POST['genre_id'];
    $album_id = !empty($_POST['album_id']) ? (int) $_POST['album_id'] : null;
    $release_date = !empty($_POST['release_date']) ? $_POST['release_date'] : null;
    $language = !empty($_POST['language']) ? $_POST['language'] : null;

    $coverImage = null;

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
    } elseif ($album_id) {
        $q = $conn->prepare("SELECT cover_image FROM albums WHERE album_id=?");
        $q->bind_param("i", $album_id);
        $q->execute();
        $r = $q->get_result()->fetch_assoc();
        $coverImage = $r['cover_image'] ?? null;
        $q->close();
    }

    if ($error === '') {
        $stmt = $conn->prepare("
            INSERT INTO songs
            (title, artist_id, genre_id, album_id, cover_image, release_date, language)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("siiisss", $title, $artist_id, $genre_id, $album_id, $coverImage, $release_date, $language);
        $stmt->execute();
        $stmt->close();

        header("Location: songs.php");
        exit;
    }
}

$artists = $conn->query("SELECT artist_id, artist_name FROM artists ORDER BY artist_name");
$genres = $conn->query("SELECT genre_id, genre_name FROM genres ORDER BY genre_name");
?>

<div id="content-wrapper" class="d-flex flex-column">
<div id="content">
<?php include 'includes/topbar.php'; ?>

<div class="container-fluid">
<h1 class="h3 mb-4 text-gray-800">Thêm bài hát</h1>

<?php if ($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">

    <div class="form-group">
        <label>Tiêu đề</label>
        <input type="text" name="title" class="form-control" required>
    </div>

    <div class="form-group">
        <label>Nghệ sĩ</label>
        <select name="artist_id" id="artistSelect" class="form-control" required>
            <option value="">-- Chọn nghệ sĩ --</option>
            <?php while ($a = $artists->fetch_assoc()): ?>
                <option value="<?= $a['artist_id'] ?>">
                    <?= htmlspecialchars($a['artist_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Thể loại</label>
        <select name="genre_id" class="form-control" required>
            <option value="">-- Chọn thể loại --</option>
            <?php while ($g = $genres->fetch_assoc()): ?>
                <option value="<?= $g['genre_id'] ?>">
                    <?= htmlspecialchars($g['genre_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Album</label>
        <select name="album_id" id="albumSelect" class="form-control">
            <option value="">-- Không --</option>
        </select>
    </div>

    <div class="form-group">
        <label>Ảnh cover (nếu có)</label>
        <input type="file" name="cover_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
        <small class="form-text text-muted">Cho phép JPG, PNG, WEBP. Tối đa 5MB.</small>
    </div>

    <div class="form-group">
        <label>Ngày phát hành</label>
        <input type="date" name="release_date" class="form-control">
    </div>

    <div class="form-group">
        <label>Ngôn ngữ</label>
        <input type="text" name="language" class="form-control" placeholder="English / Vietnamese">
    </div>

    <button class="btn btn-success">Lưu</button>
    <a href="songs.php" class="btn btn-secondary">Hủy</a>
</form>
</div>
</div>

<?php include 'includes/footer.php'; ?>
<?php include 'includes/scripts.php'; ?>

<script>
document.getElementById('artistSelect').addEventListener('change', function () {
    const artistId = this.value;
    const albumSelect = document.getElementById('albumSelect');

    albumSelect.innerHTML = '<option value="">Đang tải...</option>';

    if (!artistId) {
        albumSelect.innerHTML = '<option value="">-- Không --</option>';
        return;
    }

    fetch('ajax_get_albums.php?artist_id=' + artistId)
        .then(res => res.json())
        .then(data => {
            albumSelect.innerHTML = '<option value="">-- Không --</option>';
            data.forEach(a => {
                albumSelect.innerHTML += `<option value="${a.album_id}">${a.album_name}</option>`;
            });
        });
});
</script>
