<?php
require_once __DIR__ . '/../config/database.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$song_id = (int)($_GET['id'] ?? 0);
if ($song_id <= 0) die('Invalid song');

$songStmt = $conn->prepare("SELECT title FROM songs WHERE song_id=?");
$songStmt->bind_param("i", $song_id);
$songStmt->execute();
$song = $songStmt->get_result()->fetch_assoc();
$songStmt->close();
if (!$song) die('Song not found');

$labels = [];
$data = [];

$res = $conn->query("
    SELECT DATE(search_time) d, COUNT(*) c
    FROM search_logs
    WHERE song_id = $song_id
      AND search_time >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(search_time)
    ORDER BY d ASC
");
while ($r = $res->fetch_assoc()) {
    $labels[] = $r['d'];
    $data[]   = (int)$r['c'];
}
?>

<div id="content-wrapper" class="d-flex flex-column">
<div id="content">
<?php include 'includes/topbar.php'; ?>

<div class="container-fluid">

<h1 class="h3 mb-4 text-gray-800">
📈 Xu hướng tìm kiếm: <?= htmlspecialchars($song['title']) ?>
</h1>

<div class="card shadow mb-4">
<div class="card-body">
<?php if ($labels): ?>
    <canvas id="trendChart"></canvas>
<?php else: ?>
    <p class="text-muted text-center mb-0">
        Chưa có dữ liệu tìm kiếm trong 7 ngày gần nhất
    </p>
<?php endif; ?>
</div>
</div>

<a href="top_songs.php" class="btn btn-secondary btn-sm">← Quay lại</a>

</div>
</div>

<?php include 'includes/footer.php'; ?>
<?php include 'includes/scripts.php'; ?>

<?php if ($labels): ?>
<script>
new Chart(document.getElementById("trendChart"), {
    type: 'line',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: "Lượt tìm",
            data: <?= json_encode($data) ?>,
            borderColor: "#1cc88a",
            tension: 0.3,
            fill: false
        }]
    }
});
</script>
<?php endif; ?>
