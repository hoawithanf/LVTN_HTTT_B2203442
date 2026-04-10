<?php
require_once __DIR__ . '/../config/database.php';
include 'includes/header.php';
include 'includes/sidebar.php';

/* ================= PAGINATION ================= */
$limit = 20;
$page  = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$page  = max(1, $page);
$offset = ($page - 1) * $limit;

/* ================= COUNT ================= */
$totalRows = 0;
$q = $conn->query("SELECT COUNT(DISTINCT song_id) total FROM search_logs");
if ($q && $r = $q->fetch_assoc()) {
    $totalRows = (int)$r['total'];
}
$totalPages = max(1, ceil($totalRows / $limit));

/* ================= DATA ================= */
$stmt = $conn->prepare("
    SELECT 
        s.song_id,
        s.title,
        ar.artist_name,
        COUNT(sl.log_id) AS search_count
    FROM search_logs sl
    JOIN songs s ON s.song_id = sl.song_id
    JOIN artists ar ON ar.artist_id = s.artist_id
    GROUP BY s.song_id
    ORDER BY search_count DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$rows = $stmt->get_result();
?>

<div id="content-wrapper" class="d-flex flex-column">
<div id="content">
<?php include 'includes/topbar.php'; ?>

<div class="container-fluid">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 text-gray-800">🎵 Top bài hát được tìm nhiều nhất</h1>
    <a href="index.php" class="btn btn-secondary btn-sm">← Dashboard</a>
</div>

<div class="card shadow mb-4">
<div class="card-body p-0">

<table class="table table-hover align-middle mb-0">
<thead class="thead-light">
<tr>
    <th width="70" class="text-center">Top</th>
    <th>Bài hát</th>
    <th>Nghệ sĩ</th>
    <th width="120" class="text-center">Lượt tìm</th>
    <th width="150" class="text-center">Thao tác</th>
</tr>
</thead>
<tbody>

<?php if ($rows->num_rows > 0): ?>
<?php
$rank = $offset + 1;
while ($r = $rows->fetch_assoc()):
?>
<tr>
    <td class="text-center font-weight-bold">
        <?= $rank <= 3 ? '🏆' . $rank : $rank ?>
    </td>
    <td>
        <strong><?= htmlspecialchars($r['title']) ?></strong>
    </td>
    <td><?= htmlspecialchars($r['artist_name']) ?></td>
    <td class="text-center">
        <span class="badge badge-primary badge-pill px-3">
            <?= (int)$r['search_count'] ?>
        </span>
    </td>
    <td class="text-center">
        <a href="song_details.php?id=<?= $r['song_id'] ?>"
           class="btn btn-sm btn-info">Chi tiết</a>

        <a href="song_trend.php?id=<?= $r['song_id'] ?>"
           class="btn btn-sm btn-outline-success">📈 Trend</a>
    </td>
</tr>
<?php $rank++; endwhile; ?>
<?php else: ?>
<tr>
    <td colspan="5" class="text-center text-muted py-4">
        Chưa có dữ liệu tìm kiếm
    </td>
</tr>
<?php endif; ?>

</tbody>
</table>

</div>
</div>

<!-- PAGINATION -->
<nav>
<ul class="pagination justify-content-center">
<?php for ($p = 1; $p <= $totalPages; $p++): ?>
<li class="page-item <?= $p == $page ? 'active' : '' ?>">
    <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
</li>
<?php endfor; ?>
</ul>
</nav>

</div>
</div>

<?php
$stmt->close();
include 'includes/footer.php';
include 'includes/scripts.php';
