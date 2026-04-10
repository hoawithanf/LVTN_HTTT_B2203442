<?php
require_once __DIR__ . '/../config/database.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$result = $conn->query("
    SELECT user_id, username, email, full_name, role, created_at
    FROM users
    ORDER BY created_at DESC
");

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

$totalUsers = count($users);
$totalAdmins = 0;
$latestUserCreatedAt = null;

foreach ($users as $user) {
    if (($user['role'] ?? '') === 'admin') {
        $totalAdmins++;
    }
    if ($latestUserCreatedAt === null || $user['created_at'] > $latestUserCreatedAt) {
        $latestUserCreatedAt = $user['created_at'];
    }
}
?>

<div id="content-wrapper" class="d-flex flex-column">
<div id="content">
<?php include 'includes/topbar.php'; ?>

<div class="container-fluid admin-modern-page">

    <div class="card admin-page-hero mb-4">
        <div class="card-body py-4 px-lg-4">
            <div class="row align-items-center">
                <div class="col-lg-8 mb-4 mb-lg-0">
                    <span class="admin-page-eyebrow">Access Control</span>
                    <h1 class="admin-page-title">Quản lý người dùng</h1>
                    <p class="admin-page-subtitle">
                        Quản trị tài khoản đăng nhập, phân biệt admin và user thường, đồng thời giữ các thao tác chỉnh sửa/xóa
                        nhất quán với hệ thống auth hiện tại.
                    </p>
                    <div class="admin-meta-pills">
                        <span class="admin-meta-pill">
                            <i class="fas fa-users"></i>
                            Tổng tài khoản: <?= number_format($totalUsers) ?>
                        </span>
                        <span class="admin-meta-pill">
                            <i class="fas fa-user-shield"></i>
                            Admin: <?= number_format($totalAdmins) ?>
                        </span>
                        <span class="admin-meta-pill">
                            <i class="fas fa-user-plus"></i>
                            Tạo gần nhất: <?= $latestUserCreatedAt ? date('d/m/Y', strtotime($latestUserCreatedAt)) : 'Chưa có dữ liệu' ?>
                        </span>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="admin-hero-actions justify-content-lg-end">
                        <a href="add_user.php" class="btn btn-primary">
                            <i class="fas fa-plus mr-2"></i> Thêm người dùng
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card admin-section-card">
        <div class="card-header">
            <div class="admin-section-title">Danh sách tài khoản</div>
            <p class="admin-section-subtitle">Thông tin nhận diện, vai trò và ngày tạo để quản lý quyền truy cập.</p>
        </div>
        <div class="card-body p-0">
            <div class="admin-table-wrap">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th width="80">#</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Họ tên</th>
                            <th width="140">Vai trò</th>
                            <th width="160">Ngày tạo</th>
                            <th width="180">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users): ?>
                            <?php foreach ($users as $index => $user): ?>
                                <?php $isAdmin = ($user['role'] ?? '') === 'admin'; ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <div class="admin-name"><?= htmlspecialchars($user['username']) ?></div>
                                        <div class="admin-subtext">Mã user: #<?= (int) $user['user_id'] ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                                    <td>
                                        <span class="admin-badge-soft <?= $isAdmin ? 'admin-badge-soft-warning' : 'admin-badge-soft-info' ?>">
                                            <?= htmlspecialchars($user['role']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                                    <td>
                                        <div class="admin-actions">
                                            <a href="edit_user.php?id=<?= (int) $user['user_id'] ?>" class="btn btn-sm btn-warning">Sửa</a>
                                            <a
                                                href="delete_user.php?id=<?= (int) $user['user_id'] ?>"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Xóa user này?')"
                                            >Xóa</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">
                                    <div class="admin-empty">
                                        <i class="fas fa-users"></i>
                                        Chưa có tài khoản nào để hiển thị.
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</div>
<?php include 'includes/footer.php'; ?>
</div>
<?php include 'includes/scripts.php'; ?>
