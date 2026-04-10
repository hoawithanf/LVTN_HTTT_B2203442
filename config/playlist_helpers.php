<?php

require_once __DIR__ . '/song_helpers.php';

function nln_playlist_safe_prepare(mysqli $conn, string $sql): ?mysqli_stmt
{
    $stmt = $conn->prepare($sql);
    return $stmt instanceof mysqli_stmt ? $stmt : null;
}

function nln_playlist_ensure_schema(mysqli $conn): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $conn->query("
        CREATE TABLE IF NOT EXISTS playlists (
            playlist_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            playlist_name VARCHAR(150) NOT NULL,
            description TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_playlists_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS playlist_songs (
            playlist_song_id INT AUTO_INCREMENT PRIMARY KEY,
            playlist_id INT NOT NULL,
            song_id INT NOT NULL,
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_playlist_song (playlist_id, song_id),
            KEY idx_playlist_songs_playlist (playlist_id),
            KEY idx_playlist_songs_song (song_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $conn->query("
        ALTER TABLE playlists
        ADD COLUMN IF NOT EXISTS description TEXT NULL,
        ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ");

    $ensured = true;
}

function nln_playlist_find_user_playlist(mysqli $conn, int $userId, int $playlistId): ?array
{
    nln_playlist_ensure_schema($conn);

    $stmt = nln_playlist_safe_prepare($conn, "
        SELECT playlist_id, user_id, playlist_name, description, created_at, updated_at
        FROM playlists
        WHERE playlist_id = ? AND user_id = ?
        LIMIT 1
    ");
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('ii', $playlistId, $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

function nln_playlist_create(mysqli $conn, int $userId, string $playlistName, string $description = ''): ?array
{
    nln_playlist_ensure_schema($conn);

    $playlistName = trim($playlistName);
    $description = trim($description);
    if ($playlistName === '') {
        return null;
    }

    $existingStmt = nln_playlist_safe_prepare($conn, "
        SELECT playlist_id, user_id, playlist_name, description, created_at, updated_at
        FROM playlists
        WHERE user_id = ? AND playlist_name = ?
        LIMIT 1
    ");
    if (!$existingStmt) {
        return null;
    }

    $existingStmt->bind_param('is', $userId, $playlistName);
    $existingStmt->execute();
    $existing = $existingStmt->get_result()->fetch_assoc();
    $existingStmt->close();

    if ($existing) {
        return $existing;
    }

    $stmt = nln_playlist_safe_prepare($conn, "
        INSERT INTO playlists (user_id, playlist_name, description, created_at, updated_at)
        VALUES (?, ?, ?, NOW(), NOW())
    ");
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('iss', $userId, $playlistName, $description);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        return null;
    }

    return nln_playlist_find_user_playlist($conn, $userId, (int) $conn->insert_id);
}

function nln_playlist_add_song(mysqli $conn, int $playlistId, int $songId): array
{
    nln_playlist_ensure_schema($conn);

    $stmt = nln_playlist_safe_prepare($conn, "
        INSERT INTO playlist_songs (playlist_id, song_id, added_at)
        VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE added_at = added_at
    ");
    if (!$stmt) {
        return [
            'success' => false,
            'errno' => $conn->errno,
            'error' => $conn->error,
        ];
    }

    $stmt->bind_param('ii', $playlistId, $songId);
    $ok = $stmt->execute();
    $errno = $stmt->errno;
    $error = $stmt->error;
    $stmt->close();

    if ($ok) {
        $touchStmt = nln_playlist_safe_prepare($conn, "
            UPDATE playlists
            SET updated_at = NOW()
            WHERE playlist_id = ?
            LIMIT 1
        ");
        if ($touchStmt) {
            $touchStmt->bind_param('i', $playlistId);
            $touchStmt->execute();
            $touchStmt->close();
        }
    }

    return [
        'success' => $ok,
        'errno' => $errno,
        'error' => $error,
    ];
}

function nln_playlist_fetch_user_playlists(mysqli $conn, int $userId, int $limit = 12): array
{
    nln_playlist_ensure_schema($conn);

    $stmt = nln_playlist_safe_prepare($conn, "
        SELECT
            p.playlist_id,
            p.playlist_name,
            p.description,
            p.created_at,
            p.updated_at,
            COUNT(ps.song_id) AS song_count,
            MIN(ps.song_id) AS sample_song_id,
            MAX(COALESCE(s.cover_image, al.cover_image)) AS sample_cover
        FROM playlists p
        LEFT JOIN playlist_songs ps ON ps.playlist_id = p.playlist_id
        LEFT JOIN songs s ON s.song_id = ps.song_id
        LEFT JOIN albums al ON al.album_id = s.album_id
        WHERE p.user_id = ?
        GROUP BY p.playlist_id, p.playlist_name, p.description, p.created_at, p.updated_at
        ORDER BY p.updated_at DESC, p.playlist_id DESC
        LIMIT ?
    ");
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('ii', $userId, $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($rows as &$row) {
        $row['song_count'] = (int) ($row['song_count'] ?? 0);
        $row['cover_path'] = nln_public_song_cover_path($row['sample_cover'] ?? null, null);
    }
    unset($row);

    return $rows;
}
