<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/upload_helpers.php';

include 'includes/header.php';
include 'includes/sidebar.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: news.php");
    exit;
}

$error = '';

$stmt = $conn->prepare("SELECT * FROM news WHERE news_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$news = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$news) {
    header("Location: news.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $summary = trim($_POST['summary']);
    $content = trim($_POST['content']);
    $imageName = null;

    if (!empty($_FILES['image']['name'])) {
        $upload = nln_upload_image(
            $_FILES['image'],
            __DIR__ . '/../public/assets/img/news'
        );

        if (!$upload['success']) {
            $error = $upload['error'];
        } else {
            $imageName = $upload['filename'];
        }
    }

    if ($error === '') {
        if ($imageName !== null) {
            $stmt = $conn->prepare("
                UPDATE news
                SET title = ?, summary = ?, content = ?, image = ?
                WHERE news_id = ?
            ");
            $stmt->bind_param("ssssi", $title, $summary, $content, $imageName, $id);
        } else {
            $stmt = $conn->prepare("
                UPDATE news
                SET title = ?, summary = ?, content = ?
                WHERE news_id = ?
            ");
            $stmt->bind_param("sssi", $title, $summary, $content, $id);
        }

        $stmt->execute();
        $stmt->close();

        header("Location: news.php");
        exit;
    }
}
?>

<div id="content-wrapper" class="d-flex flex-column">
<div id="content">

<?php include 'includes/topbar.php'; ?>

<div class="container-fluid">

<h1 class="h3 mb-4 text-gray-800">Sua tin tuc</h1>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <div class="form-group">
        <label>Tieu de</label>
        <input type="text" name="title" value="<?= htmlspecialchars($news['title']) ?>" class="form-control">
    </div>

    <div class="form-group">
        <label>Anh moi (neu doi)</label><br>
        <?php if (!empty($news['image'])): ?>
            <img src="../public/assets/img/news/<?= htmlspecialchars($news['image']) ?>" width="120"><br><br>
        <?php endif; ?>
        <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
    </div>

    <div class="form-group">
        <label>Tom tat</label>
        <textarea name="summary" class="form-control"><?= htmlspecialchars($news['summary']) ?></textarea>
    </div>

    <div class="form-group">
        <label>Noi dung</label>
        <textarea name="content" class="form-control" rows="6"><?= htmlspecialchars($news['content']) ?></textarea>
    </div>

    <button class="btn btn-success">Cap nhat</button>
    <a href="news.php" class="btn btn-secondary">Huy</a>
</form>

</div>
</div>

<?php include 'includes/footer.php'; ?>
</div>

<?php include 'includes/scripts.php'; ?>
