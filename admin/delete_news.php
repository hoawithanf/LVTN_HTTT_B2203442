<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/../config/database.php';

if (!isset($_GET['id'])) {
    header('Location: news.php');
    exit;
}

$news_id = (int) $_GET['id'];

$stmt = $conn->prepare("DELETE FROM notifications WHERE news_id = ?");
$stmt->bind_param("i", $news_id);
$stmt->execute();
$stmt->close();

$stmt = $conn->prepare("DELETE FROM news WHERE news_id = ?");
$stmt->bind_param("i", $news_id);
$stmt->execute();
$stmt->close();

header("Location: news.php");
exit;
