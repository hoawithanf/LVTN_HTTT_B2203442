<?php
require_once __DIR__ . '/../config/database.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$res = $conn->query("
    SELECT ar.artist_id, ar.artist_name, COUNT(sl.log_id) AS search_count
    FROM search_logs sl
    JOIN songs s ON s.song_id = sl.song_id
    JOIN artists ar ON ar.artist_id = s.artist_id
    GROUP BY ar.artist_id
    ORDER BY search_count DESC
");
?>

<div id="content-wrapper" class="d-flex flex-column">
<div id="content">
<?php include 'includes/topbar.php'; ?>

<div class="container-fluid">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 text-gray-800">🎤 Top Nghệ sĩ được tìm nhiều</h1>
    <a href="index.php" class="btn btn-secondary btn-sm">← Dashboard</a>
</div>

<div class="card shadow">
<div class="card-body p-0">

<table class="table table-hover mb-0">
<thead class="thead-light">
<tr>
    <th width="70" class="text-center">Top</th>
    <th>Nghệ sĩ</th>
    <th width="120" class="text-center">Lượt tìm</th>
    <th width="150" class="text-center">Thao tác</th>
</tr>
</thead>
<tbody>

<?php
$i = 1;
while ($r = $res->fetch_assoc()):
?>
<tr>
    <td class="text-center font-weight-bold">
        <?= $i <= 3 ? '🏆'.$i : $i ?>
    </td>
    <td><?= htmlspecialchars($r['artist_name']) ?></td>
    <td class="text-center">
        <span class="badge badge-info badge-pill px-3">
            <?= (int)$r['search_count'] ?>
        </span>
    </td>
    <td class="text-center">
        <a href="songs.php?artist_id=<?= $r['artist_id'] ?>"
           class="btn btn-sm btn-outline-primary">
            Xem bài hát
        </a>
    </td>
</tr>
<?php $i++; endwhile; ?>

</tbody>
</table>

</div>
</div>

</div>
</div>

<?php include 'includes/footer.php'; ?>
<?php include 'includes/scripts.php'; ?>
