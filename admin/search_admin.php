<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/admin_auth.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$q = trim($_GET['q'] ?? '');
?>

<div id="content-wrapper" class="d-flex flex-column">
<div id="content">
<?php include 'includes/topbar.php'; ?>

<div class="container-fluid">
<h1 class="h3 mb-4 text-gray-800">Kết quả tìm kiếm</h1>

<?php if ($q === ''): ?>
<p class="text-muted">Vui lòng nhập từ khóa.</p>
<?php else: ?>

<h5 class="mt-4">Songs</h5>
<?php
$stmt = $conn->prepare("
    SELECT s.song_id, s.title, a.artist_name
    FROM songs s
    JOIN artists a ON s.artist_id = a.artist_id
    WHERE s.title LIKE ?
");
$like = "%$q%";
$stmt->bind_param("s", $like);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()):
?>
<div>
    <a href="edit_song.php?id=<?= $r['song_id'] ?>">
        <?= htmlspecialchars($r['title']) ?> - <?= htmlspecialchars($r['artist_name']) ?>
    </a>
</div>
<?php endwhile; ?>

<h5 class="mt-4">Artists</h5>
<?php
$stmt = $conn->prepare("SELECT artist_id, artist_name FROM artists WHERE artist_name LIKE ?");
$stmt->bind_param("s", $like);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()):
?>
<div>
    <a href="edit_artist.php?id=<?= $r['artist_id'] ?>">
        <?= htmlspecialchars($r['artist_name']) ?>
    </a>
</div>
<?php endwhile; ?>

<h5 class="mt-4">Users</h5>
<?php
$stmt = $conn->prepare("
    SELECT user_id, username, email
    FROM users
    WHERE username LIKE ? OR email LIKE ?
");
$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()):
?>
<div>
    <a href="edit_user.php?id=<?= $r['user_id'] ?>">
        <?= htmlspecialchars($r['username']) ?> (<?= htmlspecialchars($r['email']) ?>)
    </a>
</div>
<?php endwhile; ?>

<h5 class="mt-4">News</h5>
<?php
$stmt = $conn->prepare("SELECT news_id, title FROM news WHERE title LIKE ?");
$stmt->bind_param("s", $like);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()):
?>
<div>
    <a href="edit_news.php?id=<?= $r['news_id'] ?>">
        <?= htmlspecialchars($r['title']) ?>
    </a>
</div>
<?php endwhile; ?>

<?php endif; ?>

</div>
</div>

<?php include 'includes/footer.php'; ?>
