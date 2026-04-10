<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/upload_helpers.php';

$admin_id = $_SESSION['admin_id'];

$stmt = $conn->prepare("
    SELECT user_id, username, email, role, avatar, created_at
    FROM users
    WHERE user_id = ? AND role = 'admin'
    LIMIT 1
");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    die("Admin không tồn tại.");
}

$admin = $res->fetch_assoc();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $avatarPath = $admin['avatar'];

    if ($username === '' || $email === '') {
        $error = "Vui lòng nhập đầy đủ thông tin.";
    } else {
        if (!empty($_POST['avatar_base64'])) {
            $upload = nln_save_base64_image(
                $_POST['avatar_base64'],
                __DIR__ . '/uploads/avatars',
                'admin_' . $admin_id
            );

            if (!$upload['success']) {
                $error = $upload['error'];
            } else {
                $avatarPath = 'uploads/avatars/' . $upload['filename'];
            }
        }

        if ($error === '') {
            $upd = $conn->prepare("
                UPDATE users
                SET username = ?, email = ?, avatar = ?
                WHERE user_id = ? AND role = 'admin'
            ");
            $upd->bind_param("sssi", $username, $email, $avatarPath, $admin_id);

            if ($upd->execute()) {
                $_SESSION['admin_username'] = $username;
                $_SESSION['admin_avatar'] = $avatarPath;
                $success = "Cập nhật thông tin thành công.";
                $admin['username'] = $username;
                $admin['email'] = $email;
                $admin['avatar'] = $avatarPath;
            } else {
                $error = "Không thể cập nhật thông tin.";
            }
        }
    }
}
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div id="content-wrapper" class="d-flex flex-column">
<div id="content">

<?php include 'includes/topbar.php'; ?>

<div class="container-fluid">
<h1 class="h3 mb-4 text-gray-800">Admin Profile</h1>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="row">

<div class="col-lg-6">
<div class="card shadow mb-4">
<div class="card-body">

<form method="post" id="profileForm">
<input type="hidden" name="avatar_base64" id="avatar_base64">

<div class="form-group">
<label>Tên đăng nhập</label>
<input type="text" name="username" class="form-control" value="<?= htmlspecialchars($admin['username']) ?>" required>
</div>

<div class="form-group">
<label>Email</label>
<input type="email" name="email" class="form-control" value="<?= htmlspecialchars($admin['email']) ?>" required>
</div>

<button class="btn btn-primary">
<i class="fas fa-save mr-1"></i> Lưu thay đổi
</button>

<a href="change_password.php" class="btn btn-warning ml-2">
<i class="fas fa-key mr-1"></i> Đổi mật khẩu
</a>

</form>

</div>
</div>
</div>

<div class="col-lg-6">
<div class="card shadow mb-4">
<div class="card-body text-center">

<img
    id="avatarPreview"
    src="<?= $admin['avatar'] ? htmlspecialchars($admin['avatar']) : 'img/undraw_profile.svg' ?>"
    class="img-profile rounded-circle mb-3"
    style="width:140px;height:140px;object-fit:cover"
>

<input type="file" id="avatarInput" accept=".jpg,.jpeg,.png,.webp,image/*" class="form-control mt-2">

<p class="small text-muted mt-2">
Ảnh vuông, có thể crop trước khi lưu. Hỗ trợ JPG, PNG, WEBP, tối đa 5MB.
</p>

</div>
</div>
</div>

</div>
</div>
</div>

<?php include 'includes/footer.php'; ?>
<?php include 'includes/scripts.php'; ?>

<link href="https://unpkg.com/cropperjs/dist/cropper.css" rel="stylesheet">
<script src="https://unpkg.com/cropperjs/dist/cropper.js"></script>

<script>
let cropper;
const input = document.getElementById('avatarInput');
const preview = document.getElementById('avatarPreview');
const hidden = document.getElementById('avatar_base64');

input.addEventListener('change', e => {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = () => {
        preview.src = reader.result;
        cropper && cropper.destroy();
        cropper = new Cropper(preview, {
            aspectRatio: 1,
            viewMode: 1,
        });
    };
    reader.readAsDataURL(file);
});

document.getElementById('profileForm').addEventListener('submit', () => {
    if (cropper) {
        hidden.value = cropper.getCroppedCanvas({
            width: 300,
            height: 300
        }).toDataURL('image/png');
    }
});
</script>
