<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/song_helpers.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$limit = 50;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$artistFilter = isset($_GET['artist_id']) ? (int) $_GET['artist_id'] : 0;
$genreFilter = isset($_GET['genre_id']) ? (int) $_GET['genre_id'] : 0;
$searchSort = $_GET['search_sort'] ?? '';
$titleFilter = trim($_GET['title'] ?? '');

$artists = $conn->query("SELECT artist_id, artist_name FROM artists ORDER BY artist_name");
$genres = $conn->query("SELECT genre_id, genre_name FROM genres ORDER BY genre_name");

$where = [];
$params = [];
$types = '';

if ($artistFilter > 0) {
    $where[] = "s.artist_id = ?";
    $params[] = $artistFilter;
    $types .= 'i';
}

if ($genreFilter > 0) {
    $where[] = "s.genre_id = ?";
    $params[] = $genreFilter;
    $types .= 'i';
}

if ($titleFilter !== '') {
    $where[] = "s.title LIKE ?";
    $params[] = '%' . $titleFilter . '%';
    $types .= 's';
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$orderBy = 's.created_at DESC';
if ($searchSort === 'desc') {
    $orderBy = 'search_count DESC';
} elseif ($searchSort === 'asc') {
    $orderBy = 'search_count ASC';
}

$countSql = "
    SELECT COUNT(DISTINCT s.song_id)
    FROM songs s
    $whereSQL
";
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
        s.song_id,
        s.title,
        ar.artist_name,
        s.cover_image AS song_cover,
        al.cover_image AS album_cover,
        g.genre_name,
        COUNT(sl.log_id) AS search_count
    FROM songs s
    JOIN artists ar ON ar.artist_id = s.artist_id
    LEFT JOIN albums al ON al.album_id = s.album_id
    LEFT JOIN genres g ON g.genre_id = s.genre_id
    LEFT JOIN search_logs sl ON sl.song_id = s.song_id
    $whereSQL
    GROUP BY s.song_id
    ORDER BY $orderBy
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types . 'ii', ...array_merge($params, [$limit, $offset]));
} else {
    $stmt->bind_param('ii', $limit, $offset);
}
$stmt->execute();
$songsResult = $stmt->get_result();

$songs = [];
while ($row = $songsResult->fetch_assoc()) {
    $songs[] = $row;
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
                    <span class="admin-page-eyebrow">Lyrics Catalog</span>
                    <h1 class="admin-page-title">Quản lý bài hát</h1>
                    <p class="admin-page-subtitle">
                        Theo dõi danh mục bài hát theo tiêu đề, nghệ sĩ, thể loại và lượt tìm kiếm. Bộ lọc và phân trang
                        được giữ nguyên logic hiện tại, chỉ đồng bộ lại giao diện theo dashboard.
                    </p>
                    <div class="admin-meta-pills">
                        <span class="admin-meta-pill">
                            <i class="fas fa-music"></i>
                            Tổng bài hát: <?= number_format($totalRows) ?>
                        </span>
                        <span class="admin-meta-pill">
                            <i class="fas fa-filter"></i>
                            Bộ lọc đang dùng: <?= number_format(count($where)) ?>
                        </span>
                        <span class="admin-meta-pill">
                            <i class="fas fa-layer-group"></i>
                            Trang hiện tại: <?= number_format($page) ?>/<?= number_format($totalPages) ?>
                        </span>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="admin-hero-actions justify-content-lg-end">
                        <a href="add_song.php" class="btn btn-primary">
                            <i class="fas fa-plus mr-2"></i> Thêm bài hát
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card admin-section-card mb-4">
        <div class="card-header">
            <div class="admin-section-title">Bộ lọc dữ liệu</div>
            <p class="admin-section-subtitle">Tìm nhanh theo tiêu đề, nghệ sĩ, thể loại hoặc sắp xếp theo lượt tìm.</p>
        </div>
        <div class="card-body">
            <form method="get" class="admin-filter-form">
                <div class="admin-filter-span-4">
                    <label class="admin-form-label">Tên bài hát</label>
                    <input
                        type="text"
                        name="title"
                        class="form-control"
                        placeholder="Nhập tên bài hát..."
                        value="<?= htmlspecialchars($titleFilter) ?>"
                    >
                </div>

                <div class="admin-filter-span-3">
                    <label class="admin-form-label">Nghệ sĩ</label>
                    <select name="artist_id" class="form-control">
                        <option value="">Tất cả nghệ sĩ</option>
                        <?php while ($artist = $artists->fetch_assoc()): ?>
                            <option value="<?= (int) $artist['artist_id'] ?>" <?= $artistFilter === (int) $artist['artist_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($artist['artist_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="admin-filter-span-2">
                    <label class="admin-form-label">Thể loại</label>
                    <select name="genre_id" class="form-control">
                        <option value="">Tất cả thể loại</option>
                        <?php while ($genre = $genres->fetch_assoc()): ?>
                            <option value="<?= (int) $genre['genre_id'] ?>" <?= $genreFilter === (int) $genre['genre_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($genre['genre_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="admin-filter-span-3">
                    <label class="admin-form-label">Sắp xếp lượt tìm</label>
                    <select name="search_sort" class="form-control">
                        <option value="">Mặc định</option>
                        <option value="desc" <?= $searchSort === 'desc' ? 'selected' : '' ?>>Nhiều đến ít</option>
                        <option value="asc" <?= $searchSort === 'asc' ? 'selected' : '' ?>>Ít đến nhiều</option>
                    </select>
                </div>

                <div class="admin-filter-span-12">
                    <div class="admin-actions">
                        <button class="btn btn-primary">Lọc dữ liệu</button>
                        <a href="songs.php" class="btn btn-light border">Đặt lại</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card admin-section-card">
        <div class="card-header">
            <div class="admin-toolbar">
                <div>
                    <div class="admin-section-title">Danh sách bài hát</div>
                    <p class="admin-section-subtitle">Duy trì thao tác chi tiết, sửa và xóa như hiện tại.</p>
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
                            <th>Bài hát</th>
                            <th>Nghệ sĩ</th>
                            <th>Thể loại</th>
                            <th width="130">Lượt tìm</th>
                            <th width="240">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($songs): ?>
                            <?php foreach ($songs as $index => $song): ?>
                                <?php $img = nln_admin_song_cover_path($song['song_cover'] ?? null, $song['album_cover'] ?? null, "../public/assets/img/no-img.jpg"); ?>
                                <tr>
                                    <td><?= $offset + $index + 1 ?></td>
                                    <td>
                                        <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($song['title']) ?>" class="admin-thumb">
                                    </td>
                                    <td>
                                        <div class="admin-name"><?= htmlspecialchars($song['title']) ?></div>
                                        <div class="admin-subtext">Mã bài hát: #<?= (int) $song['song_id'] ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($song['artist_name']) ?></td>
                                    <td>
                                        <span class="admin-badge-soft admin-badge-soft-info">
                                            <?= htmlspecialchars($song['genre_name'] ?? 'Chưa gán') ?>
                                        </span>
                                    </td>
                                    <td><?= number_format((int) $song['search_count']) ?></td>
                                    <td>
                                        <div class="admin-actions">
                                            <a href="song_details.php?id=<?= (int) $song['song_id'] ?>" class="btn btn-sm btn-info">Chi tiết</a>
                                            <a href="edit_song.php?id=<?= (int) $song['song_id'] ?>" class="btn btn-sm btn-warning">Sửa</a>
                                            <a
                                                href="delete_song.php?id=<?= (int) $song['song_id'] ?>"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Xóa bài hát này?')"
                                            >Xóa</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">
                                    <div class="admin-empty">
                                        <i class="fas fa-music"></i>
                                        Không tìm thấy bài hát nào khớp với bộ lọc hiện tại.
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="p-3 border-top">
                    <nav aria-label="Song pagination">
                        <ul class="pagination admin-pagination justify-content-center mb-0">
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                    <a
                                        class="page-link"
                                        href="?page=<?= $p ?>&title=<?= urlencode($titleFilter) ?>&artist_id=<?= $artistFilter ?>&genre_id=<?= $genreFilter ?>&search_sort=<?= urlencode($searchSort) ?>"
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
include 'includes/scripts.php';
