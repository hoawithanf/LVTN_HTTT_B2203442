<?php

function nln_album_ratings_ensure_table(mysqli $conn): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $sql = "
        CREATE TABLE IF NOT EXISTS album_ratings (
            rating_id INT AUTO_INCREMENT PRIMARY KEY,
            album_id INT NOT NULL,
            user_id INT NOT NULL,
            rating TINYINT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_album_user (album_id, user_id),
            KEY idx_album (album_id),
            KEY idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    $conn->query($sql);
    $ensured = true;
}

function nln_album_rating_summary(mysqli $conn, int $albumId): array
{
    nln_album_ratings_ensure_table($conn);

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS rating_count, AVG(rating) AS average_rating
        FROM album_ratings
        WHERE album_id = ?
    ");
    $stmt->bind_param("i", $albumId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();

    return [
        'rating_count' => (int) ($row['rating_count'] ?? 0),
        'average_rating' => $row['average_rating'] !== null ? round((float) $row['average_rating'], 1) : 0.0,
    ];
}

function nln_album_user_rating(mysqli $conn, int $albumId, int $userId): ?int
{
    nln_album_ratings_ensure_table($conn);

    $stmt = $conn->prepare("
        SELECT rating
        FROM album_ratings
        WHERE album_id = ? AND user_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $albumId, $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ? (int) $row['rating'] : null;
}

function nln_album_save_rating(mysqli $conn, int $albumId, int $userId, int $rating): array
{
    nln_album_ratings_ensure_table($conn);

    $rating = max(1, min(5, $rating));

    $stmt = $conn->prepare("
        INSERT INTO album_ratings (album_id, user_id, rating)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE rating = VALUES(rating), updated_at = CURRENT_TIMESTAMP
    ");
    $stmt->bind_param("iii", $albumId, $userId, $rating);
    $stmt->execute();
    $stmt->close();

    return nln_album_rating_summary($conn, $albumId);
}
