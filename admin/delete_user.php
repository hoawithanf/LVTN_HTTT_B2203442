<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/../config/database.php';

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: users.php");
    exit;
}

$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM users WHERE role = 'admin'");
$stmt->execute();
$admins = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
$stmt->close();

$stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$role = $stmt->get_result()->fetch_assoc()['role'] ?? '';
$stmt->close();

if ($role === 'admin' && $admins <= 1) {
    header("Location: users.php");
    exit;
}

$stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

header("Location: users.php");
exit;
