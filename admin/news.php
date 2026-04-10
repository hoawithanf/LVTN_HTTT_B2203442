<?php
require_once __DIR__ . '/../config/database.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$result = $conn->query("
    SELECT news_id, title, image, created_at
    FROM news
    ORDER BY created_at DESC
");

$newsItems = [];
while ($row = $result->fetch_assoc()) {
    $newsItems[] = $row;
}

$totalNews = count($newsItems);
$newsWithImage = 0;
$latestCreatedAt = null;

foreach ($newsItems as $item) {
    if (!empty($item['image'])) {
        $newsWithImage++;
    }
    if ($latestCreatedAt === null || $item['created_at'] > $latestCreatedAt) {
        $latestCreatedAt = $item['created_at'];
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
                    <span class="admin-page-eyebrow">Content Hub</span>
                    <h1 class="admin-page-title">Quản lý tin tức</h1>
                    <p class="admin-page-subtitle">
                        Theo dõi toàn bộ bài viết đang hiển thị trên public, giữ luồng biên tập gọn và thao tác sửa/xóa nhanh
                        mà không thay đổi route hay chức năng hiện có.
                    </p>
                    <div class="admin-meta-pills">
                        <span class="admin-meta-pill">
                            <i class="fas fa-newspaper"></i>
                            Tổng tin: <?= number_format($totalNews) ?>
                        </span>
                        <span class="admin-meta-pill">
                            <i class="fas fa-image"></i>
                            Có ảnh: <?= number_format($newsWithImage) ?>
                        </span>
                        <span class="admin-meta-pill">
                            <i class="fas fa-clock"></i>
                            Cập nhật gần nhất: <?= $latestCreatedAt ? date('d/m/Y H:i', strtotime($latestCreatedAt)) : 'Chưa có dữ liệu' ?>
                        </span>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="admin-hero-actions justify-content-lg-end">
                        <a href="add_news.php" class="btn btn-primary">
                            <i class="fas fa-plus mr-2"></i> Thêm tin tức
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card admin-section-card">
        <div class="card-header">
            <div class="admin-toolbar">
                <div>
                    <div class="admin-section-title">Danh sách bài viết</div>
                    <p class="admin-section-subtitle">Kiểm tra tiêu đề, ảnh đại diện và thời điểm đăng của từng tin tức.</p>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="admin-table-wrap">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th width="80">#</th>
                            <th width="120">Ảnh</th>
                            <th>Tiêu đề</th>
                            <th width="180">Ngày tạo</th>
                            <th width="220">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($newsItems): ?>
                            <?php foreach ($newsItems as $index => $row): ?>
                                <?php
                                $newsImage = !empty($row['image'])
                                    ? "../public/assets/img/news/" . $row['image']
                                    : "../public/assets/img/default-news.jpg";
                                ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <img
                                            src="<?= htmlspecialchars($newsImage) ?>"
                                            alt="<?= htmlspecialchars($row['title']) ?>"
                                            class="admin-thumb admin-thumb-sm"
                                        >
                                    </td>
                                    <td>
                                        <div class="admin-name"><?= htmlspecialchars($row['title']) ?></div>
                                        <div class="admin-subtext">Mã tin: #<?= (int) $row['news_id'] ?></div>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <div class="admin-actions">
                                            <a href="edit_news.php?id=<?= (int) $row['news_id'] ?>" class="btn btn-sm btn-warning">Sửa</a>
                                            <a
                                                href="delete_news.php?id=<?= (int) $row['news_id'] ?>"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Xóa tin này?')"
                                            >Xóa</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">
                                    <div class="admin-empty">
                                        <i class="fas fa-newspaper"></i>
                                        Chưa có tin tức nào để hiển thị.
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
