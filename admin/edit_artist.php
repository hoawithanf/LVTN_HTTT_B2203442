<?php
require_once __DIR__ . '/../config/database.php';
include 'includes/header.php';
include 'includes/sidebar.php';

/* ================= VALIDATE ID ================= */
$artist_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($artist_id <= 0) {
    header("Location: artists.php");
    exit;
}

/* ================= LOAD ARTIST ================= */
$stmt = $conn->prepare("
    SELECT artist_name, avatar, bio, country, birth_year
    FROM artists
    WHERE artist_id = ?
");
$stmt->bind_param("i", $artist_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    header("Location: artists.php");
    exit;
}

$artist = $res->fetch_assoc();

/* ================= UPDATE ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name    = trim($_POST['artist_name']);
    $bio     = $_POST['bio'];
    $country = trim($_POST['country']);
    $birth   = !empty($_POST['birth_year']) ? (int)$_POST['birth_year'] : null;

    $avatarName = $artist['avatar'];

    if (!empty($_FILES['avatar']['name'])) {
        $avatarName = time() . '_' . basename($_FILES['avatar']['name']);
        move_uploaded_file(
            $_FILES['avatar']['tmp_name'],
            __DIR__ . '/../public/assets/img/artists/' . $avatarName
        );
    }

    $update = $conn->prepare("
        UPDATE artists
        SET artist_name = ?, avatar = ?, bio = ?, country = ?, birth_year = ?
        WHERE artist_id = ?
    ");
    $update->bind_param(
        "ssssii",
        $name,
        $avatarName,
        $bio,
        $country,
        $birth,
        $artist_id
    );
    $update->execute();

    header("Location: artists.php");
    exit;
}
?>

<div id="content-wrapper" class="d-flex flex-column">
<div id="content">

<?php include 'includes/topbar.php'; ?>

<div class="container-fluid">

<h1 class="h3 mb-4 text-gray-800">Sửa nghệ sĩ</h1>

<form method="post" enctype="multipart/form-data">

    <div class="form-group">
        <label>Tên nghệ sĩ</label>
        <input type="text" name="artist_name" class="form-control"
               value="<?= htmlspecialchars($artist['artist_name']) ?>" required>
    </div>

    <div class="form-group">
        <label>Avatar hiện tại</label><br>
        <?php
        $avatarPath = $artist['avatar']
            ? "../public/assets/img/artists/" . $artist['avatar']
            : "../public/assets/img/no-avatar.png";
        ?>
        <img src="<?= $avatarPath ?>" class="img-thumbnail mb-2"
             style="width:120px;height:120px;object-fit:cover">
    </div>

    <div class="form-group">
        <label>Đổi avatar (nếu có)</label>
        <input type="file" name="avatar" class="form-control">
    </div>

    <div class="form-group">
        <label>Tiểu sử</label>
        <textarea name="bio" id="editor" class="form-control" rows="6">
<?= htmlspecialchars($artist['bio']) ?>
        </textarea>
    </div>

    <div class="form-group">
        <label>Quốc gia</label>
        <input type="text" name="country" class="form-control"
               value="<?= htmlspecialchars($artist['country']) ?>">
    </div>

    <div class="form-group">
        <label>Năm sinh</label>
        <input type="number" name="birth_year" class="form-control"
               value="<?= htmlspecialchars($artist['birth_year']) ?>">
    </div>

    <button class="btn btn-success">Cập nhật</button>
    <a href="artists.php" class="btn btn-secondary">Hủy</a>

</form>

</div>
</div>

<?php include 'includes/footer.php'; ?>
<?php include 'includes/scripts.php'; ?>

<!-- CKEditor -->
<script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
<script>
CKEDITOR.replace('editor');
</script>
