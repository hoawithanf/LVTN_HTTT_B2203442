<?php

require_once __DIR__ . '/../../config/openai_helpers.php';

function analyzeSongMeaning_OpenAI($lyrics, $title = '', $artist = '')
{
    $title = trim((string) $title);
    $artist = trim((string) $artist);
    $lyrics = trim((string) $lyrics);

    if ($lyrics === '') {
        return ['success' => false, 'error' => 'Lyrics trống, không thể phân tích.'];
    }

    $instructions = "Bạn là trợ lý phân tích âm nhạc bằng tiếng Việt. "
        . "Hãy giải thích ý nghĩa bài hát theo 2 đến 4 đoạn ngắn, rõ ràng, dễ hiểu. "
        . "Không dùng bullet, không dùng emoji, không bịa thêm dữ kiện ngoài phần lyrics đã cho. "
        . "Nếu có ẩn dụ hoặc cảm xúc nổi bật thì giải thích ngắn gọn.";

    $context = "Tiêu đề: " . ($title !== '' ? $title : 'Không rõ') . "\n"
        . "Nghệ sĩ: " . ($artist !== '' ? $artist : 'Không rõ') . "\n\n"
        . "Lyrics:\n" . $lyrics;

    $response = nln_openai_text_response($instructions, $context, [
        'max_output_tokens' => 500,
    ]);

    if (!$response['success']) {
        return $response;
    }

    return [
        'success' => true,
        'meaning' => trim($response['text']),
        'model' => $response['model'] ?? null,
    ];
}
