<?php
include('../includes/session.php');
include('../includes/database.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'NOT_LOGIN'
    ]);
    exit;
}

$user_id  = (int)$_SESSION['user_id'];
$album_id = (int)($_POST['album_id'] ?? 0);

if ($album_id <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'INVALID_ALBUM'
    ]);
    exit;
}

/* CHECK EXISTS */
$chk = $conn->prepare("
    SELECT id FROM album_favorites
    WHERE user_id=? AND album_id=?
");
$chk->bind_param("ii", $user_id, $album_id);
$chk->execute();
$rs = $chk->get_result();

if ($rs->num_rows > 0) {
    /* UNFAVORITE */
    $del = $conn->prepare("
        DELETE FROM album_favorites
        WHERE user_id=? AND album_id=?
    ");
    $del->bind_param("ii", $user_id, $album_id);
    $del->execute();

    echo json_encode([
        'success'   => true,
        'favorited'=> false
    ]);
} else {
    /* FAVORITE */
    $ins = $conn->prepare("
        INSERT INTO album_favorites (user_id, album_id)
        VALUES (?,?)
    ");
    $ins->bind_param("ii", $user_id, $album_id);
    $ins->execute();

    echo json_encode([
        'success'   => true,
        'favorited'=> true
    ]);
}
