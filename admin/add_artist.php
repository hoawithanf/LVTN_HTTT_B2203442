<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/upload_helpers.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['artist_name'] ?? '');
    $bio = $_POST['bio'] ?? '';
    $country = trim($_POST['country'] ?? '');
    $birth = !empty($_POST['birth_year']) ? (int) $_POST['birth_year'] : null;

    $avatar = null;
    if (!empty($_FILES['avatar']['name'])) {
        $upload = nln_upload_image(
            $_FILES['avatar'],
            __DIR__ . '/../public/assets/img/artists',
            'artist'
        );

        if (!$upload['success']) {
            $error = $upload['error'];
        } else {
            $avatar = $upload['filename'];
        }
    }

    if ($error === '') {
        $stmt = $conn->prepare("
            INSERT INTO artists (artist_name, avatar, bio, country, birth_year)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssi", $name, $avatar, $bio, $country, $birth);
        $stmt->execute();
        $stmt->close();

        header("Location: artists.php");
        exit;
    }
}
?>

<div id="content-wrapper" class="d-flex flex-column">
<div id="content">
<?php include 'includes/topbar.php'; ?>

<div class="container-fluid">
<h1 class="h3 mb-4 text-gray-800">Thêm nghệ sĩ</h1>

<?php if ($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <div class="form-group">
        <label>Tên nghệ sĩ</label>
        <input type="text" name="artist_name" class="form-control" required>
    </div>

    <div class="form-group">
        <label>Avatar</label>
        <input type="file" name="avatar" class="form-control" accept=".jpg,.jpeg,.png,.webp">
        <small class="form-text text-muted">Cho phép JPG, PNG, WEBP. Tối đa 5MB.</small>
    </div>

    <div class="form-group">
        <label>Tiểu sử</label>
        <textarea name="bio" id="editor" class="form-control"></textarea>
    </div>

    <div class="form-group">
        <label>Quốc gia</label>
        <input type="text" name="country" class="form-control">
    </div>

    <div class="form-group">
        <label>Năm sinh</label>
        <input type="number" name="birth_year" class="form-control">
    </div>

    <button class="btn btn-success">Lưu</button>
    <a href="artists.php" class="btn btn-secondary">Hủy</a>
</form>
</div>
</div>

<?php include 'includes/footer.php'; ?>
<?php include 'includes/scripts.php'; ?>

<script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
<script>
CKEDITOR.replace('editor');
</script>
