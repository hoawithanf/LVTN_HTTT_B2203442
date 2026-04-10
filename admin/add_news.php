<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/upload_helpers.php';

include 'includes/header.php';
include 'includes/sidebar.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $summary = trim($_POST['summary']);
    $content = trim($_POST['content']);
    $artist_id = !empty($_POST['artist_id']) ? (int) $_POST['artist_id'] : null;

    $imageName = null;
    if (!empty($_FILES['image']['name'])) {
        $upload = nln_upload_image(
            $_FILES['image'],
            __DIR__ . '/../public/assets/img/news',
            'news'
        );

        if (!$upload['success']) {
            $error = $upload['error'];
        } else {
            $imageName = $upload['filename'];
        }
    }

    if ($error === '') {
        $stmt = $conn->prepare("
            INSERT INTO news (title, image, summary, content, artist_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssi", $title, $imageName, $summary, $content, $artist_id);
        $stmt->execute();

        $news_id = $conn->insert_id;
        $stmt->close();

        if ($artist_id) {
            $followers = $conn->prepare("
                SELECT DISTINCT user_id
                FROM artist_follows
                WHERE artist_id = ?
            ");
            $followers->bind_param("i", $artist_id);
            $followers->execute();
            $res = $followers->get_result();

            $insert = $conn->prepare("
                INSERT INTO notifications (user_id, news_id, artist_id)
                SELECT ?, ?, ?
                FROM DUAL
                WHERE NOT EXISTS (
                    SELECT 1 FROM notifications
                    WHERE user_id = ?
                      AND news_id = ?
                )
            ");

            while ($f = $res->fetch_assoc()) {
                $insert->bind_param("iiiii", $f['user_id'], $news_id, $artist_id, $f['user_id'], $news_id);
                $insert->execute();
            }

            $insert->close();
            $followers->close();
        }

        header("Location: news.php");
        exit;
    }
}

$artists = $conn->query("
    SELECT artist_id, artist_name
    FROM artists
    ORDER BY artist_name
");
?>

<div id="content-wrapper" class="d-flex flex-column">
<div id="content">

<?php include 'includes/topbar.php'; ?>

<div class="container-fluid">

<h1 class="h3 mb-4 text-gray-800">Thêm tin tức</h1>

<?php if ($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">

    <div class="form-group">
        <label>Tiêu đề</label>
        <input type="text" name="title" class="form-control" required>
    </div>

    <div class="form-group">
        <label>Ảnh</label>
        <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
        <small class="form-text text-muted">Cho phép JPG, PNG, WEBP. Tối đa 5MB.</small>
    </div>

    <div class="form-group">
        <label>Tóm tắt</label>
        <textarea name="summary" class="form-control" required></textarea>
    </div>

    <div class="form-group">
        <label>Nội dung</label>
        <textarea name="content" class="form-control" rows="6"></textarea>
    </div>

    <div class="form-group">
        <label>Nghệ sĩ (nếu có)</label>
        <select name="artist_id" class="form-control">
            <option value="">-- Không --</option>
            <?php while ($a = $artists->fetch_assoc()): ?>
                <option value="<?= $a['artist_id'] ?>">
                    <?= htmlspecialchars($a['artist_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <button class="btn btn-success">Lưu</button>
    <a href="news.php" class="btn btn-secondary">Hủy</a>

</form>

</div>
</div>

<?php include 'includes/footer.php'; ?>
<?php include 'includes/scripts.php'; ?>
