<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_helpers.php';

$admin_id = (int) $_SESSION['admin_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($new !== $confirm) {
        $error = "Mật khẩu mới không khớp.";
    } else {
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id=? AND role='admin'");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        $valid = $res && nln_verify_password($old, $res['password_hash']);

        if (!$valid) {
            $error = "Mật khẩu cũ không đúng.";
        } else {
            $newHash = nln_hash_password($new);
            $up = $conn->prepare("UPDATE users SET password_hash=? WHERE user_id=?");
            $up->bind_param("si", $newHash, $admin_id);
            $up->execute();
            $success = "Đổi mật khẩu thành công.";
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
<h1 class="h3 mb-4 text-gray-800">Đổi mật khẩu</h1>

<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<form method="post" class="col-md-6 p-0">
    <div class="form-group">
        <label>Mật khẩu cũ</label>
        <input type="password" name="old_password" class="form-control" required>
    </div>
    <div class="form-group">
        <label>Mật khẩu mới</label>
        <input type="password" name="new_password" class="form-control" required>
    </div>
    <div class="form-group">
        <label>Nhập lại mật khẩu mới</label>
        <input type="password" name="confirm_password" class="form-control" required>
    </div>
    <button class="btn btn-primary">Cập nhật</button>
</form>
</div>

</div>
<?php include 'includes/footer.php'; ?>
</div>
<?php include 'includes/scripts.php'; ?>
