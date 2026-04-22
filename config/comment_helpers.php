<?php

function nln_comment_ensure_schema(mysqli $conn): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $columns = [];
    $result = $conn->query("SHOW COLUMNS FROM comments");
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }

    if (!in_array('parent_comment_id', $columns, true)) {
        $conn->query("ALTER TABLE comments ADD COLUMN parent_comment_id INT NULL DEFAULT NULL AFTER user_id");
        $conn->query("ALTER TABLE comments ADD INDEX idx_comments_parent (parent_comment_id)");
    }

    if (!in_array('updated_at', $columns, true)) {
        $conn->query("ALTER TABLE comments ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
        $conn->query("UPDATE comments SET updated_at = created_at WHERE updated_at IS NULL");
    }

    $conn->query("
        CREATE TABLE IF NOT EXISTS comment_likes (
            like_id INT AUTO_INCREMENT PRIMARY KEY,
            comment_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_comment_like (comment_id, user_id),
            KEY idx_comment_likes_comment (comment_id),
            KEY idx_comment_likes_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $ensured = true;
}

function nln_comment_safe_html(string $value): string
{
    return nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
}

function nln_comment_fetch_tree(mysqli $conn, int $songId, int $currentUserId = 0, bool $adminDelete = false): array
{
    nln_comment_ensure_schema($conn);

    $stmt = $conn->prepare("
        SELECT
            c.comment_id,
            c.song_id,
            c.user_id,
            c.parent_comment_id,
            c.content,
            c.created_at,
            c.updated_at,
            u.username,
            COUNT(cl.like_id) AS like_count,
            MAX(CASE WHEN cl.user_id = ? THEN 1 ELSE 0 END) AS liked_by_me
        FROM comments c
        JOIN users u ON u.user_id = c.user_id
        LEFT JOIN comment_likes cl ON cl.comment_id = c.comment_id
        WHERE c.song_id = ?
        GROUP BY c.comment_id, c.song_id, c.user_id, c.parent_comment_id, c.content, c.created_at, c.updated_at, u.username
        ORDER BY c.parent_comment_id IS NOT NULL ASC, c.created_at DESC
    ");
    $stmt->bind_param("ii", $currentUserId, $songId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $byId = [];
    foreach ($rows as $row) {
        $commentId = (int) $row['comment_id'];
        $userId = (int) $row['user_id'];
        $updatedAt = (string) ($row['updated_at'] ?? '');
        $createdAt = (string) ($row['created_at'] ?? '');
        $isEdited = $updatedAt !== '' && $createdAt !== '' && strtotime($updatedAt) > strtotime($createdAt);

        $byId[$commentId] = [
            'comment_id' => $commentId,
            'song_id' => (int) $row['song_id'],
            'user_id' => $userId,
            'parent_comment_id' => $row['parent_comment_id'] !== null ? (int) $row['parent_comment_id'] : null,
            'username' => (string) $row['username'],
            'username_html' => htmlspecialchars((string) $row['username'], ENT_QUOTES, 'UTF-8'),
            'content' => (string) $row['content'],
            'content_html' => nln_comment_safe_html((string) $row['content']),
            'content_input_html' => htmlspecialchars((string) $row['content'], ENT_QUOTES, 'UTF-8'),
            'created_at' => $createdAt,
            'created_at_html' => htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8'),
            'updated_at' => $updatedAt,
            'updated_at_html' => htmlspecialchars($updatedAt, ENT_QUOTES, 'UTF-8'),
            'is_edited' => $isEdited,
            'like_count' => (int) ($row['like_count'] ?? 0),
            'liked_by_me' => ((int) ($row['liked_by_me'] ?? 0)) === 1,
            'can_edit' => $currentUserId > 0 && $currentUserId === $userId,
            'can_delete' => $currentUserId > 0 && ($currentUserId === $userId || $adminDelete),
            'can_reply' => $currentUserId > 0,
            'replies' => [],
        ];
    }

    $rootCommentIds = [];
    foreach (array_keys($byId) as $commentId) {
        $parentId = $byId[$commentId]['parent_comment_id'];
        if ($parentId !== null && isset($byId[$parentId])) {
            $byId[$parentId]['replies'][] = $byId[$commentId];
            continue;
        }

        $rootCommentIds[] = $commentId;
    }

    $tree = [];
    foreach ($rootCommentIds as $commentId) {
        $tree[] = $byId[$commentId];
    }

    return nln_comment_sort_tree($tree);
}

function nln_comment_sort_tree(array $comments): array
{
    usort($comments, function ($a, $b) {
        $aCreatedAt = (string) ($a['created_at'] ?? '');
        $bCreatedAt = (string) ($b['created_at'] ?? '');
        return strtotime($bCreatedAt) <=> strtotime($aCreatedAt);
    });

    foreach ($comments as &$comment) {
        if (!empty($comment['replies'])) {
            $comment['replies'] = nln_comment_sort_tree($comment['replies']);
        }
    }
    unset($comment);

    return $comments;
}

function nln_comment_delete(mysqli $conn, int $commentId): void
{
    nln_comment_ensure_schema($conn);

    $childStmt = $conn->prepare("SELECT comment_id FROM comments WHERE parent_comment_id = ?");
    $childStmt->bind_param("i", $commentId);
    $childStmt->execute();
    $childIds = array_map('intval', array_column($childStmt->get_result()->fetch_all(MYSQLI_ASSOC), 'comment_id'));
    $childStmt->close();

    foreach ($childIds as $childId) {
        nln_comment_delete($conn, $childId);
    }

    $deleteLikes = $conn->prepare("DELETE FROM comment_likes WHERE comment_id = ?");
    $deleteLikes->bind_param("i", $commentId);
    $deleteLikes->execute();
    $deleteLikes->close();

    $deleteComment = $conn->prepare("DELETE FROM comments WHERE comment_id = ?");
    $deleteComment->bind_param("i", $commentId);
    $deleteComment->execute();
    $deleteComment->close();
}
