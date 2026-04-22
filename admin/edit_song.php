<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/upload_helpers.php';
require_once __DIR__ . '/../config/lyric_request_helpers.php';

include 'includes/header.php';
include 'includes/sidebar.php';

$song_id = (int) ($_GET['id'] ?? 0);
$request_id = (int) ($_GET['request_id'] ?? ($_POST['request_id'] ?? 0));

if ($song_id <= 0) {
    header("Location: songs.php");
    exit;
}

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

nln_lyric_request_ensure_schema($conn);

$error = '';
$selectedRequest = $request_id > 0 ? nln_lyric_request_fetch_by_id($conn, $request_id) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((string) ($_POST['title'] ?? ''));
    $artist_id = (int) ($_POST['artist_id'] ?? 0);
    $genre_id = (int) ($_POST['genre_id'] ?? 0);
    $album_id = !empty($_POST['album_id']) ? (int) $_POST['album_id'] : null;
    $release_date = $_POST['release_date'] ?: null;
    $language = $_POST['language'] ?: null;
    $lyrics = $_POST['lyrics'] ?: null;
    $lyrics_vi = $_POST['lyrics_vi'] ?: null;
    $meaning = $_POST['meaning'] ?: null;
    $resolve_request = isset($_POST['resolve_request']) ? 1 : 0;

    $coverImage = (string) ($_POST['old_cover'] ?? '');

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
        $hasLyricsViColumn = false;
        $columnsResult = $conn->query("SHOW COLUMNS FROM songs LIKE 'lyrics_vi'");
        if ($columnsResult instanceof mysqli_result) {
            $hasLyricsViColumn = $columnsResult->num_rows > 0;
            $columnsResult->close();
        }

        if ($hasLyricsViColumn) {
            $stmt = $conn->prepare("
                UPDATE songs SET
                    title = ?,
                    artist_id = ?,
                    genre_id = ?,
                    album_id = ?,
                    cover_image = ?,
                    release_date = ?,
                    language = ?,
                    lyrics = ?,
                    lyrics_vi = ?,
                    meaning = ?
                WHERE song_id = ?
            ");
            $stmt->bind_param("siiissssssi", $title, $artist_id, $genre_id, $album_id, $coverImage, $release_date, $language, $lyrics, $lyrics_vi, $meaning, $song_id);
        } else {
            $stmt = $conn->prepare("
                UPDATE songs SET
                    title = ?,
                    artist_id = ?,
                    genre_id = ?,
                    album_id = ?,
                    cover_image = ?,
                    release_date = ?,
                    language = ?,
                    lyrics = ?,
                    meaning = ?
                WHERE song_id = ?
            ");
            $stmt->bind_param("siiisssssi", $title, $artist_id, $genre_id, $album_id, $coverImage, $release_date, $language, $lyrics, $meaning, $song_id);
        }

        $stmt->execute();
        $stmt->close();

        if ($resolve_request && $request_id > 0) {
            $resolveStmt = $conn->prepare("
                UPDATE lyric_correction_requests
                SET status = 'resolved', resolved_at = NOW()
                WHERE request_id = ?
            ");
            if ($resolveStmt) {
                $resolveStmt->bind_param("i", $request_id);
                $resolveStmt->execute();
                $resolveStmt->close();
            }

            if ($selectedRequest && !empty($selectedRequest['user_id'])) {
                nln_create_user_notification(
                    $conn,
                    (int) $selectedRequest['user_id'],
                    'Lyrics đã được cập nhật',
                    'Yêu cầu chỉnh sửa lyrics cho bài "' . (string) $song['title'] . '" đã được admin xử lý.',
                    'post.php?id=' . (int) $song_id
                );
            }
        }

        header("Location: songs.php");
        exit;
    }
}

$songStmt = $conn->prepare("SELECT * FROM songs WHERE song_id = ?");
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
$albums = $conn->query("SELECT album_id, album_name FROM albums WHERE artist_id = " . (int) $song['artist_id'] . " ORDER BY album_name");

$songRequests = [];
$songRequestsStmt = $conn->prepare("
    SELECT
        r.request_id,
        r.request_message,
        r.status,
        r.created_at,
        u.username
    FROM lyric_correction_requests r
    JOIN users u ON u.user_id = r.user_id
    WHERE r.song_id = ?
    ORDER BY
        CASE WHEN r.status = 'pending' THEN 0 ELSE 1 END,
        r.created_at DESC
");
$songRequestsStmt->bind_param("i", $song_id);
$songRequestsStmt->execute();
$songRequestsResult = $songRequestsStmt->get_result();
while ($songRequestsResult && $row = $songRequestsResult->fetch_assoc()) {
    $songRequests[] = $row;
}
$songRequestsStmt->close();
?>

<div id="content-wrapper" class="d-flex flex-column">
<div id="content">
<?php include 'includes/topbar.php'; ?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Sửa bài hát</h1>

    <?php if ($selectedRequest): ?>
        <div class="alert alert-info">
            <strong>Yêu cầu đang xử lý:</strong><br>
            Người gửi: <?= h($selectedRequest['username']) ?><br>
            Bài hát: <?= h($selectedRequest['song_title']) ?> - <?= h($selectedRequest['artist_name']) ?><br>
            Nội dung: <?= nl2br(h($selectedRequest['request_message'])) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($songRequests)): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Lịch sử yêu cầu chỉnh sửa lyrics</h6>
            </div>
            <div class="card-body">
                <?php foreach ($songRequests as $request): ?>
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <strong><?= h($request['username']) ?></strong>
                                <small class="text-muted d-block"><?= date('d/m/Y H:i', strtotime((string) $request['created_at'])) ?></small>
                            </div>
                            <span class="badge badge-<?= $request['status'] === 'pending' ? 'warning' : 'success' ?>">
                                <?= h($request['status']) ?>
                            </span>
                        </div>
                        <div><?= nl2br(h($request['request_message'])) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="old_cover" value="<?= h($song['cover_image'] ?? '') ?>">
        <input type="hidden" name="request_id" value="<?= (int) $request_id ?>">

        <div class="form-group">
            <label>Tiêu đề</label>
            <input type="text" name="title" class="form-control" value="<?= h($song['title']) ?>" required>
        </div>

        <div class="form-group">
            <label>Nghệ sĩ</label>
            <select name="artist_id" id="artistSelect" class="form-control" required>
                <?php while ($artist = $artists->fetch_assoc()): ?>
                    <option value="<?= (int) $artist['artist_id'] ?>" <?= (int) $artist['artist_id'] === (int) $song['artist_id'] ? 'selected' : '' ?>>
                        <?= h($artist['artist_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Thể loại</label>
            <select name="genre_id" class="form-control" required>
                <?php while ($genre = $genres->fetch_assoc()): ?>
                    <option value="<?= (int) $genre['genre_id'] ?>" <?= (int) $genre['genre_id'] === (int) $song['genre_id'] ? 'selected' : '' ?>>
                        <?= h($genre['genre_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Album</label>
            <select name="album_id" id="albumSelect" class="form-control">
                <option value="">-- Không --</option>
                <?php while ($album = $albums->fetch_assoc()): ?>
                    <option value="<?= (int) $album['album_id'] ?>" <?= (int) $album['album_id'] === (int) ($song['album_id'] ?? 0) ? 'selected' : '' ?>>
                        <?= h($album['album_name']) ?>
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
            <input type="date" name="release_date" value="<?= h($song['release_date']) ?>" class="form-control">
        </div>

        <div class="form-group">
            <label>Ngôn ngữ</label>
            <input type="text" name="language" value="<?= h($song['language']) ?>" class="form-control">
        </div>

        <div class="form-group">
            <label>Lyrics</label>
            <textarea name="lyrics" class="form-control" rows="8"><?= h($song['lyrics']) ?></textarea>
        </div>

        <?php if (array_key_exists('lyrics_vi', $song)): ?>
            <div class="form-group">
                <label>Lyrics tiếng Việt</label>
                <textarea name="lyrics_vi" class="form-control" rows="8"><?= h($song['lyrics_vi']) ?></textarea>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label>Meaning</label>
            <textarea name="meaning" class="form-control" rows="8"><?= h($song['meaning']) ?></textarea>
        </div>

        <?php if ($selectedRequest && ($selectedRequest['status'] ?? '') === 'pending'): ?>
            <div class="form-group form-check">
                <input type="checkbox" class="form-check-input" id="resolveRequest" name="resolve_request" checked>
                <label class="form-check-label" for="resolveRequest">Đánh dấu yêu cầu này đã xử lý sau khi cập nhật bài hát</label>
            </div>
        <?php endif; ?>

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
        .then((res) => res.json())
        .then((data) => {
            const select = document.getElementById('albumSelect');
            select.innerHTML = '<option value="">-- Không --</option>';
            data.forEach((album) => {
                select.innerHTML += `<option value="${album.album_id}">${album.album_name}</option>`;
            });
        });
});
</script>
