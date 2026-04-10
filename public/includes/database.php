<?php
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';
$db = getenv('DB_DATABASE') ?: 'nln_lyrics';
$port = (int) (getenv('DB_PORT') ?: 3306);

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die('Ket noi that bai: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
?>
