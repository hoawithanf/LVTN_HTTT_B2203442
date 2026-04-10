<?php
header('Content-Type: application/json; charset=UTF-8');

include('session.php');
include('database.php');
require_once __DIR__ . '/../../config/album_rating_helpers.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Bạn cần đăng nhập để đánh giá album.',
    ]);
    exit;
}

$albumId = isset($_POST['album_id']) ? (int) $_POST['album_id'] : 0;
$rating = isset($_POST['rating']) ? (int) $_POST['rating'] : 0;

if ($albumId <= 0 || $rating < 1 || $rating > 5) {
    echo json_encode([
        'success' => false,
        'error' => 'Dữ liệu đánh giá không hợp lệ.',
    ]);
    exit;
}

$summary = nln_album_save_rating($conn, $albumId, (int) $_SESSION['user_id'], $rating);

echo json_encode([
    'success' => true,
    'rating' => $rating,
    'rating_count' => $summary['rating_count'],
    'average_rating' => number_format((float) $summary['average_rating'], 1),
]);
