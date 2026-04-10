<?php
// Legacy compatibility route.
// Song detail is officially served by public/post.php.

$songId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$target = $songId > 0 ? 'post.php?id=' . $songId : 'search.php';

header('Location: ' . $target, true, 302);
exit;
