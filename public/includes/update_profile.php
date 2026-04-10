<?php
// public/includes/update_profile.php
header('Content-Type: application/json; charset=UTF-8');
include('database.php');
include('session.php');

// Kiểm tra login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Bạn chưa đăng nhập.']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Lấy dữ liệu POST
$full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$email     = isset($_POST['email']) ? trim($_POST['email']) : '';
// CSRF nếu có: $_POST['csrf_token']

// Validate
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Email không hợp lệ.']);
    exit;
}

// Kiểm tra email đã tồn tại (không phải của chính user)
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
$stmt->bind_param("si", $email, $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Email đã được sử dụng bởi tài khoản khác.']);
    exit;
}
$stmt->close();

// Cập nhật
$u = $conn->prepare("UPDATE users SET full_name = ?, email = ? WHERE user_id = ?");
$u->bind_param("ssi", $full_name, $email, $user_id);
$ok = $u->execute();
$u->close();

if ($ok) {
    echo json_encode(['success' => true, 'message' => 'Cập nhật thông tin cá nhân thành công.']);
} else {
    echo json_encode(['success' => false, 'error' => 'Lỗi khi cập nhật cơ sở dữ liệu.']);
}
exit;
