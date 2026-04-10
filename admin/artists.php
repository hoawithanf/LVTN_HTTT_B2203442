<?php
require_once __DIR__ . '/../config/database.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$q = $conn->query("SELECT * FROM artists ORDER BY created_at DESC");

$artists = [];
while ($row = $q->fetch_assoc()) {
    $artists[] = $row;
}

$totalArtists = count($artists);
$artistsWithAvatar = 0;
$countries = [];

foreach ($artists as $artist) {
    if (!empty($artist['avatar'])) {
        $artistsWithAvatar++;
    }
    if (!empty($artist['country'])) {
        $countries[$artist['country']] = true;
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
                    <span class="admin-page-eyebrow">Artist Directory</span>
                    <h1 class="admin-page-title">Quản lý nghệ sĩ</h1>
                    <p class="admin-page-subtitle">
                        Giữ kho hồ sơ nghệ sĩ nhất quán với phần public, theo dõi nhanh avatar, quốc gia và các bản ghi cần chỉnh sửa.
                    </p>
                    <div class="admin-meta-pills">
                        <span class="admin-meta-pill">
                            <i class="fas fa-microphone"></i>
                            Tổng nghệ sĩ: <?= number_format($totalArtists) ?>
                        </span>
                        <span class="admin-meta-pill">
                            <i class="fas fa-image-portrait"></i>
                            Có avatar: <?= number_format($artistsWithAvatar) ?>
                        </span>
                        <span class="admin-meta-pill">
                            <i class="fas fa-globe-asia"></i>
                            Quốc gia khác nhau: <?= number_format(count($countries)) ?>
                        </span>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="admin-hero-actions justify-content-lg-end">
                        <a href="add_artist.php" class="btn btn-primary">
                            <i class="fas fa-plus mr-2"></i> Thêm nghệ sĩ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card admin-section-card">
        <div class="card-header">
            <div class="admin-section-title">Danh sách nghệ sĩ</div>
            <p class="admin-section-subtitle">Thông tin chính để chỉnh sửa nhanh hoặc xóa bản ghi không còn dùng.</p>
        </div>
        <div class="card-body p-0">
            <div class="admin-table-wrap">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th width="80">#</th>
                            <th width="120">Avatar</th>
                            <th>Nghệ sĩ</th>
                            <th width="180">Quốc gia</th>
                            <th width="130">Năm sinh</th>
                            <th width="180">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($artists): ?>
                            <?php foreach ($artists as $index => $artist): ?>
                                <?php
                                $avatar = !empty($artist['avatar'])
                                    ? "../public/assets/img/artists/" . $artist['avatar']
                                    : "../public/assets/img/no-avatar.png";
                                ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <img
                                            src="<?= htmlspecialchars($avatar) ?>"
                                            alt="<?= htmlspecialchars($artist['artist_name']) ?>"
                                            class="admin-thumb"
                                        >
                                    </td>
                                    <td>
                                        <div class="admin-name"><?= htmlspecialchars($artist['artist_name']) ?></div>
                                        <div class="admin-subtext">Mã nghệ sĩ: #<?= (int) $artist['artist_id'] ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($artist['country'] ?: 'Chưa cập nhật') ?></td>
                                    <td><?= $artist['birth_year'] ? (int) $artist['birth_year'] : '—' ?></td>
                                    <td>
                                        <div class="admin-actions">
                                            <a href="edit_artist.php?id=<?= (int) $artist['artist_id'] ?>" class="btn btn-sm btn-warning">Sửa</a>
                                            <a
                                                href="delete_artist.php?id=<?= (int) $artist['artist_id'] ?>"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Xóa nghệ sĩ này?')"
                                            >Xóa</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">
                                    <div class="admin-empty">
                                        <i class="fas fa-microphone"></i>
                                        Chưa có nghệ sĩ nào trong hệ thống.
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
<?php include 'includes/scripts.php'; ?>
