<?php

require_once __DIR__ . '/../../config/openai_helpers.php';

function nln_lyrics_translation_ensure_schema(mysqli $conn): void
{
    $columns = [];
    $result = $conn->query("SHOW COLUMNS FROM songs");
    if ($result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $columns[] = (string) ($row['Field'] ?? '');
        }
        $result->close();
    }

    if (!in_array('lyrics_vi', $columns, true)) {
        $conn->query("ALTER TABLE songs ADD COLUMN lyrics_vi LONGTEXT NULL DEFAULT NULL AFTER lyrics");
    }
}

function nln_lyrics_translation_save_cache(mysqli $conn, int $songId, string $lyricsVi): void
{
    if ($songId <= 0 || trim($lyricsVi) === '') {
        return;
    }

    nln_lyrics_translation_ensure_schema($conn);

    $stmt = $conn->prepare("UPDATE songs SET lyrics_vi = ? WHERE song_id = ?");
    if (!$stmt) {
        return;
    }

    $stmt->bind_param("si", $lyricsVi, $songId);
    $stmt->execute();
    $stmt->close();
}

function nln_lyrics_translation_get_cache(mysqli $conn, int $songId): string
{
    if ($songId <= 0) {
        return '';
    }

    nln_lyrics_translation_ensure_schema($conn);

    $stmt = $conn->prepare("SELECT lyrics_vi FROM songs WHERE song_id = ? LIMIT 1");
    if (!$stmt) {
        return '';
    }

    $stmt->bind_param("i", $songId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return trim((string) ($row['lyrics_vi'] ?? ''));
}

function nln_translate_lyrics_to_vietnamese(string $lyrics, string $title = '', string $artist = ''): array
{
    $lyrics = trim($lyrics);
    $title = trim($title);
    $artist = trim($artist);

    if ($lyrics === '') {
        return ['success' => false, 'error' => 'Lyrics trống, không thể dịch.'];
    }

    $instructions = "Bạn là trợ lý dịch lyrics sang tiếng Việt. "
        . "Hãy dịch nghĩa tự nhiên, dễ hiểu và bám sát nội dung gốc. "
        . "Giữ nguyên cấu trúc từng dòng và các khoảng trắng dòng giữa các đoạn. "
        . "Không thêm tiêu đề, không đánh số, không thêm chú thích ngoài phần dịch. "
        . "Nếu có dòng lặp lại thì vẫn giữ lặp lại trong bản dịch.";

    $context = "Tiêu đề: " . ($title !== '' ? $title : 'Không rõ') . "\n"
        . "Nghệ sĩ: " . ($artist !== '' ? $artist : 'Không rõ') . "\n\n"
        . "Lyrics gốc:\n" . $lyrics;

    $response = nln_openai_text_response($instructions, $context, [
        'max_output_tokens' => 2200,
    ]);

    if (!$response['success']) {
        return $response;
    }

    return [
        'success' => true,
        'lyrics_vi' => trim((string) $response['text']),
        'model' => $response['model'] ?? null,
    ];
}
