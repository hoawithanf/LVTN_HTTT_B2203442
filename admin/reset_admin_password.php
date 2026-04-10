<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_helpers.php';

$newPass = 'admin123';
$hash = nln_hash_password($newPass);

$stmt = $conn->prepare("
    UPDATE users
    SET password_hash = ?
    WHERE role = 'admin'
");
$stmt->bind_param("s", $hash);
$stmt->execute();
$stmt->close();

echo "Reset mật khẩu admin thành công. Password: $newPass";
