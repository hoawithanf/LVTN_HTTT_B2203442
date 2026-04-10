<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_helpers.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $fullName = trim($_POST['full_name']);
    $role = $_POST['role'];

    $check = $conn->prepare("SELECT user_id FROM users WHERE username=? OR email=?");
    $check->bind_param("ss", $username, $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $error = "Username hoặc email đã tồn tại.";
    } else {
        $hash = nln_hash_password($password);
        $stmt = $conn->prepare("
            INSERT INTO users (username, email, password_hash, full_name, role)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssss", $username, $email, $hash, $fullName, $role);
        $stmt->execute();
        header("Location: users.php");
        exit;
    }
}
?>

<div id="content-wrapper" class="d-flex flex-column">
<div id="content">
<?php include 'includes/topbar.php'; ?>

<div class="container-fluid">
<h1 class="h3 mb-4">Thêm user</h1>

<?php if ($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post">
    <div class="form-group">
        <label>Username</label>
        <input name="username" class="form-control" required>
    </div>

    <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" class="form-control" required>
    </div>

    <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>

    <div class="form-group">
        <label>Họ tên</label>
        <input name="full_name" class="form-control">
    </div>

    <div class="form-group">
        <label>Role</label>
        <select name="role" class="form-control">
            <option value="user">User</option>
            <option value="admin">Admin</option>
        </select>
    </div>

    <button class="btn btn-success">Lưu</button>
    <a href="users.php" class="btn btn-secondary">Hủy</a>
</form>
</div>
</div>
<?php include 'includes/footer.php'; ?>
</div>
<?php include 'includes/scripts.php'; ?>
