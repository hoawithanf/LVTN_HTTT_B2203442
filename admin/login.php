<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_helpers.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("
        SELECT user_id, username, password_hash
        FROM users
        WHERE username = ? AND role = 'admin'
        LIMIT 1
    ");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $admin = $res->fetch_assoc();
        $valid = nln_verify_password($password, $admin['password_hash']);

        if ($valid) {
            if (nln_password_needs_rehash($admin['password_hash'])) {
                nln_upgrade_password_hash($conn, (int) $admin['user_id'], $password);
            }

            $_SESSION['admin_id'] = $admin['user_id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_role'] = 'admin';

            header("Location: index.php");
            exit;
        }
    }

    $error = "Sai tài khoản hoặc mật khẩu";
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-primary">

<div class="container">
<div class="row justify-content-center">
<div class="col-xl-5 col-lg-6 col-md-8">
<div class="card shadow mt-5">
<div class="card-body p-4">

<h4 class="text-center mb-3">Admin Login</h4>

<?php if ($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post">
    <div class="form-group">
        <input class="form-control" name="username" placeholder="Username" required>
    </div>
    <div class="form-group">
        <input class="form-control" type="password" name="password" placeholder="Password" required>
    </div>
    <button class="btn btn-primary btn-block">Login</button>
</form>

</div>
</div>
</div>
</div>
</div>

</body>
</html>
