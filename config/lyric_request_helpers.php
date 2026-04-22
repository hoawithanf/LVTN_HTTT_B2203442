<?php

function nln_lyric_request_ensure_schema(mysqli $conn): void
{
    $conn->query("
        CREATE TABLE IF NOT EXISTS lyric_correction_requests (
            request_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            song_id INT NOT NULL,
            request_message TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            admin_note TEXT NULL DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            resolved_at TIMESTAMP NULL DEFAULT NULL,
            INDEX idx_lcr_status (status),
            INDEX idx_lcr_song (song_id),
            INDEX idx_lcr_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $notificationColumns = [];
    $notificationResult = $conn->query("SHOW COLUMNS FROM notifications");
    if ($notificationResult instanceof mysqli_result) {
        while ($row = $notificationResult->fetch_assoc()) {
            $notificationColumns[] = (string) ($row['Field'] ?? '');
        }
        $notificationResult->close();
    }

    if (!in_array('custom_title', $notificationColumns, true)) {
        $conn->query("ALTER TABLE notifications ADD COLUMN custom_title VARCHAR(255) NULL DEFAULT NULL AFTER artist_id");
    }

    if (!in_array('custom_message', $notificationColumns, true)) {
        $conn->query("ALTER TABLE notifications ADD COLUMN custom_message TEXT NULL DEFAULT NULL AFTER custom_title");
    }

    if (!in_array('redirect_url', $notificationColumns, true)) {
        $conn->query("ALTER TABLE notifications ADD COLUMN redirect_url VARCHAR(255) NULL DEFAULT NULL AFTER custom_message");
    }
}

function nln_lyric_request_pending_count(mysqli $conn): int
{
    nln_lyric_request_ensure_schema($conn);

    $result = $conn->query("SELECT COUNT(*) AS total FROM lyric_correction_requests WHERE status = 'pending'");
    if ($result instanceof mysqli_result) {
        $row = $result->fetch_assoc();
        $result->close();
        return (int) ($row['total'] ?? 0);
    }

    return 0;
}

function nln_lyric_request_fetch_by_id(mysqli $conn, int $requestId): ?array
{
    nln_lyric_request_ensure_schema($conn);

    $stmt = $conn->prepare("
        SELECT
            r.*,
            u.username,
            s.title AS song_title,
            a.artist_name
        FROM lyric_correction_requests r
        JOIN users u ON u.user_id = r.user_id
        JOIN songs s ON s.song_id = r.song_id
        JOIN artists a ON a.artist_id = s.artist_id
        WHERE r.request_id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("i", $requestId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row ?: null;
}

function nln_lyric_request_status_label(string $status): string
{
    $map = [
        'pending' => 'Chờ xử lý',
        'resolved' => 'Đã xử lý',
    ];

    return $map[$status] ?? $status;
}

function nln_create_user_notification(
    mysqli $conn,
    int $userId,
    string $title,
    string $message,
    string $redirectUrl
): void {
    nln_lyric_request_ensure_schema($conn);

    if ($userId <= 0 || trim($title) === '' || trim($redirectUrl) === '') {
        return;
    }

    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, news_id, artist_id, custom_title, custom_message, redirect_url, is_read, created_at)
        VALUES (?, NULL, NULL, ?, ?, ?, 0, NOW())
    ");

    if (!$stmt) {
        return;
    }

    $stmt->bind_param("isss", $userId, $title, $message, $redirectUrl);
    $stmt->execute();
    $stmt->close();
}
