<?php
include('../includes/session.php');
include('../includes/database.php');

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$artist_id = isset($_POST['artist_id']) ? (int) $_POST['artist_id'] : 0;

if ($artist_id <= 0) {
    echo json_encode(['success' => false]);
    exit;
}

$check = $conn->prepare("SELECT id FROM artist_follows WHERE user_id=? AND artist_id=?");
$check->bind_param("ii", $user_id, $artist_id);
$check->execute();

$isFollowing = false;
if ($check->get_result()->num_rows > 0) {
    // UNFOLLOW
    $del = $conn->prepare("DELETE FROM artist_follows WHERE user_id=? AND artist_id=?");
    $del->bind_param("ii", $user_id, $artist_id);
    $del->execute();
    $del->close();
} else {
    // FOLLOW
    $ins = $conn->prepare("INSERT INTO artist_follows (user_id, artist_id) VALUES (?,?)");
    $ins->bind_param("ii", $user_id, $artist_id);
    $ins->execute();
    $ins->close();
    $isFollowing = true;
}

$check->close();

$count = $conn->prepare("SELECT COUNT(*) AS total FROM artist_follows WHERE artist_id = ?");
$count->bind_param("i", $artist_id);
$count->execute();
$followerCount = (int) ($count->get_result()->fetch_assoc()['total'] ?? 0);
$count->close();

echo json_encode([
    'success' => true,
    'following' => $isFollowing,
    'follower_count' => $followerCount,
]);
