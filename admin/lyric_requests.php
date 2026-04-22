<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/lyric_request_helpers.php';

nln_lyric_request_ensure_schema($conn);

$statusFilter = trim((string) ($_GET['status'] ?? 'all'));
if (!in_array($statusFilter, ['all', 'pending', 'resolved'], true)) {
    $statusFilter = 'all';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = (int) ($_POST['request_id'] ?? 0);
    $action = trim((string) ($_POST['action'] ?? ''));

    if ($requestId > 0 && in_array($action, ['resolve', 'reopen'], true)) {
        $status = $action === 'resolve' ? 'resolved' : 'pending';
        $resolvedAt = $action === 'resolve' ? 'NOW()' : 'NULL';
        $conn->query("
            UPDATE lyric_correction_requests
            SET status = '{$status}', resolved_at = {$resolvedAt}
            WHERE request_id = {$requestId}
        ");
    }
}

$requests = [];
$filterSql = '';
if ($statusFilter === 'pending') {
    $filterSql = "WHERE r.status = 'pending'";
} elseif ($statusFilter === 'resolved') {
    $filterSql = "WHERE r.status = 'resolved'";
}

$result = $conn->query("
    SELECT
        r.*,
        u.username,
        s.title AS song_title,
        a.artist_name
    FROM lyric_correction_requests r
    JOIN users u ON u.user_id = r.user_id
    JOIN songs s ON s.song_id = r.song_id
    JOIN artists a ON a.artist_id = s.artist_id
    {$filterSql}
    ORDER BY
        CASE WHEN r.status = 'pending' THEN 0 ELSE 1 END,
        r.created_at DESC
");

if ($result instanceof mysqli_result) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
    $result->close();
}

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$pendingCount = nln_lyric_request_pending_count($conn);
$resolvedCount = 0;
$countResult = $conn->query("
    SELECT status, COUNT(*) AS total
    FROM lyric_correction_requests
    GROUP BY status
");

if ($countResult instanceof mysqli_result) {
    while ($countRow = $countResult->fetch_assoc()) {
        if (($countRow['status'] ?? '') === 'resolved') {
            $resolvedCount = (int) ($countRow['total'] ?? 0);
        }
    }
    $countResult->close();
}

$unreadNotifications = $pendingCount;
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div id="content-wrapper" class="d-flex flex-column">
<div id="content">
<?php include 'includes/topbar.php'; ?>

<div class="container-fluid admin-modern-page">

    <div class="card admin-page-hero mb-4">
        <div class="card-body py-4 px-lg-4">
            <div class="row align-items-center">
                <div class="col-lg-8 mb-4 mb-lg-0">
                    <span class="admin-page-eyebrow">Lyrics Moderation</span>
                    <h1 class="admin-page-title">Yêu cầu sửa lyrics</h1>
                    <p class="admin-page-subtitle">
                        Theo dõi phản hồi từ người dùng, mở nhanh tới trang sửa bài hát và cập nhật lại phần lyrics theo yêu cầu.
                    </p>
                    <div class="admin-meta-pills">
                        <span class="admin-meta-pill">
                            <i class="fas fa-file-signature"></i>
                            Tổng yêu cầu: <?= number_format($pendingCount + $resolvedCount) ?>
                        </span>
                        <span class="admin-meta-pill">
                            <i class="fas fa-hourglass-half"></i>
                            Chờ xử lý: <?= number_format($pendingCount) ?>
                        </span>
                        <span class="admin-meta-pill">
                            <i class="fas fa-circle-check"></i>
                            Đã xử lý: <?= number_format($resolvedCount) ?>
                        </span>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="admin-hero-actions justify-content-lg-end">
                        <a href="songs.php" class="btn btn-light border">
                            <i class="fas fa-music mr-2"></i> Quản lý bài hát
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
                    <div class="admin-section-title">Danh sách yêu cầu</div>
                    <p class="admin-section-subtitle">Lọc nhanh theo trạng thái để admin tập trung xử lý đúng nhóm yêu cầu.</p>
                </div>
                <div class="admin-toolbar-actions">
                    <div class="lyric-request-filters">
                        <a href="lyric_requests.php?status=all" class="lyric-request-filter <?= $statusFilter === 'all' ? 'is-active' : '' ?>">Tất cả</a>
                        <a href="lyric_requests.php?status=pending" class="lyric-request-filter <?= $statusFilter === 'pending' ? 'is-active' : '' ?>">Đang chờ sửa</a>
                        <a href="lyric_requests.php?status=resolved" class="lyric-request-filter <?= $statusFilter === 'resolved' ? 'is-active' : '' ?>">Đã xử lý</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (empty($requests)): ?>
                <div class="p-4 text-muted">Không có yêu cầu phù hợp với bộ lọc hiện tại.</div>
            <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="table admin-table">
                        <thead>
                            <tr>
                                <th width="90">#</th>
                                <th width="260">Bài hát</th>
                                <th width="160">Người gửi</th>
                                <th>Nội dung yêu cầu</th>
                                <th width="150">Trạng thái</th>
                                <th width="150">Thời gian</th>
                                <th width="260">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                                <?php
                                $status = (string) ($request['status'] ?? 'pending');
                                $statusLabel = nln_lyric_request_status_label($status);
                                $statusClass = $status === 'pending' ? 'admin-badge-soft-warning' : 'admin-badge-soft-success';
                                ?>
                                <tr>
                                    <td><?= (int) $request['request_id'] ?></td>
                                    <td>
                                        <div class="admin-name"><?= h($request['song_title']) ?></div>
                                        <div class="admin-subtext"><?= h($request['artist_name']) ?></div>
                                    </td>
                                    <td><?= h($request['username']) ?></td>
                                    <td style="min-width: 320px;"><?= nl2br(h($request['request_message'])) ?></td>
                                    <td>
                                        <span class="admin-badge-soft <?= $statusClass ?>">
                                            <?= h($statusLabel) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime((string) $request['created_at'])) ?></td>
                                    <td>
                                        <div class="admin-actions">
                                            <a class="btn btn-sm btn-primary" href="edit_song.php?id=<?= (int) $request['song_id'] ?>&request_id=<?= (int) $request['request_id'] ?>">
                                                Sửa lyrics
                                            </a>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="request_id" value="<?= (int) $request['request_id'] ?>">
                                                <input type="hidden" name="action" value="<?= $status === 'pending' ? 'resolve' : 'reopen' ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                    <?= $status === 'pending' ? 'Đánh dấu đã xử lý' : 'Mở lại' ?>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<?php include 'includes/footer.php'; ?>
<?php include 'includes/scripts.php'; ?>

<style>
.lyric-request-filters {
    display: inline-flex;
    align-items: center;
    gap: .65rem;
    flex-wrap: wrap;
}

.lyric-request-filter {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
    padding: .62rem 1rem;
    border: 1px solid rgba(148, 163, 184, .22);
    border-radius: 999px;
    background: #fff;
    color: #64748b;
    font-size: .86rem;
    font-weight: 700;
    text-decoration: none;
    transition: background-color .16s ease, color .16s ease, border-color .16s ease, box-shadow .16s ease;
}

.lyric-request-filter:hover,
.lyric-request-filter:focus {
    color: #2563eb;
    border-color: rgba(37, 99, 235, .2);
    background: #f8fbff;
    text-decoration: none;
}

.lyric-request-filter.is-active {
    color: #fff;
    border-color: #2563eb;
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    box-shadow: 0 12px 24px rgba(37, 99, 235, .18);
}

@media (max-width: 991.98px) {
    .admin-toolbar-actions {
        width: 100%;
    }

    .lyric-request-filters {
        width: 100%;
    }

    .lyric-request-filter {
        flex: 1 1 auto;
    }
}
</style>
