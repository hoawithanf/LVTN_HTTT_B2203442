<?php
require_once __DIR__ . '/../config/database.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) header("Location: users.php");

$stmt = $conn->prepare("
    SELECT username, email, full_name, role
    FROM users WHERE user_id=?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) header("Location: users.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = $_POST['full_name'];
    $role     = $_POST['role'];

    $up = $conn->prepare("
        UPDATE users SET full_name=?, role=? WHERE user_id=?
    ");
    $up->bind_param("ssi", $fullName, $role, $id);
    $up->execute();
    header("Location: users.php");
    exit;
}
?>

<div id="content-wrapper" class="d-flex flex-column">
<div id="content">
<?php include 'includes/topbar.php'; ?>

<div class="container-fluid">
<h1 class="h3 mb-4">Sửa User</h1>

<form method="post">
    <div class="form-group">
        <label>Username</label>
        <input class="form-control" value="<?= $user['username'] ?>" disabled>
    </div>

    <div class="form-group">
        <label>Email</label>
        <input class="form-control" value="<?= $user['email'] ?>" disabled>
    </div>

    <div class="form-group">
        <label>Họ tên</label>
        <input name="full_name" class="form-control"
               value="<?= htmlspecialchars($user['full_name']) ?>">
    </div>

    <div class="form-group">
        <label>Role</label>
        <select name="role" class="form-control">
            <option value="user" <?= $user['role']==='user'?'selected':'' ?>>User</option>
            <option value="admin" <?= $user['role']==='admin'?'selected':'' ?>>Admin</option>
        </select>
    </div>

    <button class="btn btn-success">Cập nhật</button>
    <a href="users.php" class="btn btn-secondary">Hủy</a>
</form>
</div>
</div>
<?php include 'includes/footer.php'; ?>
</div>
<?php include 'includes/scripts.php'; ?>
