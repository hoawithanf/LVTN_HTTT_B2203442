<?php
header('Content-Type: application/json; charset=UTF-8');
include('database.php');
include('session.php');
require_once __DIR__ . '/../../config/auth_helpers.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Bạn chưa đăng nhập.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$current_password = isset($_POST['current_password']) ? trim($_POST['current_password']) : '';
$new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
$confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

if (strlen($new_password) < 6) {
    echo json_encode(['success' => false, 'error' => 'Mật khẩu mới phải có ít nhất 6 ký tự.']);
    exit;
}

if ($new_password !== $confirm_password) {
    echo json_encode(['success' => false, 'error' => 'Xác nhận mật khẩu không khớp.']);
    exit;
}

$stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Người dùng không tồn tại.']);
    exit;
}
$row = $res->fetch_assoc();
$stmt->close();

if (!nln_verify_password($current_password, $row['password_hash'])) {
    echo json_encode(['success' => false, 'error' => 'Mật khẩu hiện tại không đúng.']);
    exit;
}

$new_hash = nln_hash_password($new_password);
$u = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
$u->bind_param("si", $new_hash, $user_id);
$ok = $u->execute();
$u->close();

if ($ok) {
    echo json_encode(['success' => true, 'message' => 'Đổi mật khẩu thành công.']);
} else {
    echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống khi cập nhật mật khẩu.']);
}
exit;
