<?php
require_once __DIR__ . '/../config/database.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$limit = 50;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$artist = $_GET['artist'] ?? '';
$user_id = $_GET['user_id'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$export = $_GET['export'] ?? '';

$where = [];
$params = [];
$types = '';

if ($artist !== '') {
    $where[] = "artist_name = ?";
    $params[] = $artist;
    $types .= 's';
}

if ($user_id !== '') {
    $where[] = "user_id = ?";
    $params[] = (int) $user_id;
    $types .= 'i';
}

if ($date_from !== '') {
    $where[] = "search_time >= ?";
    $params[] = $date_from . " 00:00:00";
    $types .= 's';
}

if ($date_to !== '') {
    $where[] = "search_time <= ?";
    $params[] = $date_to . " 23:59:59";
    $types .= 's';
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

if ($export === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=search_logs.csv');

    $sqlExport = "
        SELECT user_id, song_id, song_title, artist_name, search_time
        FROM search_logs
        $whereSQL
        ORDER BY search_time DESC
    ";
    $stmtExport = $conn->prepare($sqlExport);
    if ($params) {
        $stmtExport->bind_param($types, ...$params);
    }
    $stmtExport->execute();
    $res = $stmtExport->get_result();

    $out = fopen('php://output', 'w');
    fputcsv($out, ['User ID', 'Song ID', 'Bài hát', 'Nghệ sĩ', 'Thời gian tìm']);
    while ($row = $res->fetch_assoc()) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

$countSql = "SELECT COUNT(*) FROM search_logs $whereSQL";
$stmtCount = $conn->prepare($countSql);
if ($params) {
    $stmtCount->bind_param($types, ...$params);
}
$stmtCount->execute();
$stmtCount->bind_result($totalRows);
$stmtCount->fetch();
$stmtCount->close();

$totalRows = (int) $totalRows;
$totalPages = max(1, (int) ceil($totalRows / $limit));

$sql = "
    SELECT
        log_id,
        user_id,
        song_id,
        song_title,
        artist_name,
        cover_image,
        search_time
    FROM search_logs
    $whereSQL
    ORDER BY search_time DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types . "ii", ...array_merge($params, [$limit, $offset]));
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

$artists = [];
$artistQuery = $conn->query("SELECT DISTINCT artist_name FROM search_logs ORDER BY artist_name");
while ($row = $artistQuery->fetch_assoc()) {
    $artists[] = $row['artist_name'];
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
                    <span class="admin-page-eyebrow">Search Intelligence</span>
                    <h1 class="admin-page-title">Lịch sử tìm kiếm</h1>
                    <p class="admin-page-subtitle">
                        Theo dõi hoạt động tìm kiếm từ public để đánh giá bài hát, nghệ sĩ và mức độ quan tâm của người dùng.
                        Luồng export CSV và bộ lọc hiện tại được giữ nguyên.
                    </p>
                    <div class="admin-meta-pills">
                        <span class="admin-meta-pill">
                            <i class="fas fa-search"></i>
                            Tổng log: <?= number_format($totalRows) ?>
                        </span>
                        <span class="admin-meta-pill">
                            <i class="fas fa-filter"></i>
                            Bộ lọc đang dùng: <?= number_format(count($where)) ?>
                        </span>
                        <span class="admin-meta-pill">
                            <i class="fas fa-file-export"></i>
                            Xuất nhanh theo điều kiện hiện tại
                        </span>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="admin-hero-actions justify-content-lg-end">
                        <a href="?<?= htmlspecialchars(http_build_query($_GET + ['export' => 'csv'])) ?>" class="btn btn-success">
                            <i class="fas fa-file-csv mr-2"></i> Xuất CSV
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card admin-section-card mb-4">
        <div class="card-header">
            <div class="admin-section-title">Bộ lọc lịch sử</div>
            <p class="admin-section-subtitle">Lọc theo nghệ sĩ, user hoặc khoảng thời gian tìm kiếm.</p>
        </div>
        <div class="card-body">
            <form method="get" class="admin-filter-form">
                <div class="admin-filter-span-3">
                    <label class="admin-form-label">Nghệ sĩ</label>
                    <select name="artist" class="form-control">
                        <option value="">Tất cả nghệ sĩ</option>
                        <?php foreach ($artists as $artistOption): ?>
                            <option value="<?= htmlspecialchars($artistOption) ?>" <?= $artist === $artistOption ? 'selected' : '' ?>>
                                <?= htmlspecialchars($artistOption) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="admin-filter-span-2">
                    <label class="admin-form-label">User ID</label>
                    <input type="number" name="user_id" class="form-control" value="<?= htmlspecialchars($user_id) ?>">
                </div>

                <div class="admin-filter-span-2">
                    <label class="admin-form-label">Từ ngày</label>
                    <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
                </div>

                <div class="admin-filter-span-2">
                    <label class="admin-form-label">Đến ngày</label>
                    <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
                </div>

                <div class="admin-filter-span-3">
                    <label class="admin-form-label">Thao tác</label>
                    <div class="admin-actions">
                        <button class="btn btn-primary">Lọc dữ liệu</button>
                        <a href="search_logs.php" class="btn btn-light border">Đặt lại</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card admin-section-card">
        <div class="card-header">
            <div class="admin-section-title">Danh sách log</div>
            <p class="admin-section-subtitle">Ảnh minh họa, bài hát và mốc thời gian tìm kiếm gần nhất.</p>
        </div>
        <div class="card-body p-0">
            <div class="admin-table-wrap">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th width="80">#</th>
                            <th width="100">User</th>
                            <th width="100">Song</th>
                            <th width="120">Ảnh</th>
                            <th>Bài hát</th>
                            <th>Nghệ sĩ</th>
                            <th width="190">Thời gian tìm</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($rows): ?>
                            <?php foreach ($rows as $index => $row): ?>
                                <?php
                                $imgPath = "../public/assets/img/albums/" . $row['cover_image'];
                                $img = (!empty($row['cover_image']) && file_exists($imgPath))
                                    ? $imgPath
                                    : "../public/assets/img/default.jpg";
                                ?>
                                <tr>
                                    <td><?= $offset + $index + 1 ?></td>
                                    <td>
                                        <span class="admin-badge-soft admin-badge-soft-primary">#<?= (int) $row['user_id'] ?></span>
                                    </td>
                                    <td>
                                        <span class="admin-badge-soft admin-badge-soft-info">#<?= (int) $row['song_id'] ?></span>
                                    </td>
                                    <td>
                                        <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($row['song_title']) ?>" class="admin-thumb admin-thumb-sm">
                                    </td>
                                    <td>
                                        <div class="admin-name"><?= htmlspecialchars($row['song_title']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($row['artist_name']) ?></td>
                                    <td><?= date('d/m/Y H:i:s', strtotime($row['search_time'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">
                                    <div class="admin-empty">
                                        <i class="fas fa-search"></i>
                                        Không có dữ liệu tìm kiếm phù hợp với điều kiện hiện tại.
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="p-3 border-top">
                    <nav aria-label="Search logs pagination">
                        <ul class="pagination admin-pagination justify-content-center mb-0">
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                    <a
                                        class="page-link"
                                        href="?page=<?= $p ?>&artist=<?= urlencode($artist) ?>&user_id=<?= urlencode($user_id) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>"
                                    ><?= $p ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>
</div>

<?php
$stmt->close();
include 'includes/footer.php';
?>
</div>

<?php include 'includes/scripts.php'; ?>
