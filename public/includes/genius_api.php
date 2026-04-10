<?php
// includes/genius_api.php
// Genius API: SEARCH → GET SONG PATH → CRAWL HTML → PARSE LYRICS

function genius_get_lyrics($artist, $title, $token)
{
    $artist = trim($artist);
    $title  = trim($title);

    if ($artist === "" || $title === "") {
        return [
            "success" => false,
            "error"   => "Thiếu title hoặc artist"
        ];
    }

    /* ============================================================
       STEP 1 — Search Song on Genius
    ============================================================ */
    $query = urlencode("$title $artist");
    $url   = "https://api.genius.com/search?q={$query}";

    $response = curl_request($url, [
        "Authorization: Bearer $token"
    ]);

    if ($response["error"]) {
        return ["success" => false, "error" => "Lỗi CURL khi search: " . $response["error"]];
    }

    $data = json_decode($response["body"], true);

    if (empty($data["response"]["hits"])) {
        return ["success" => false, "error" => "Không tìm thấy bài hát trên Genius."];
    }

    // Best match
    $path = $data["response"]["hits"][0]["result"]["path"];
    $songUrl = "https://genius.com" . $path;


    /* ============================================================
       STEP 2 — Fetch HTML page
    ============================================================ */
    $html = curl_request($songUrl, [
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)",
        "Accept-Language: en-US,en;q=0.9",
        "Accept: text/html",
        "Referer: https://genius.com/"
    ])["body"];

    if (!$html) {
        return ["success" => false, "error" => "Không thể tải trang lyrics."];
    }

    /* ============================================================
       STEP 3 — Parse DIV Lyrics (Cấu trúc mới của Genius)
    ============================================================ */

    // Genius lyrics nằm trong nhiều container
    preg_match_all(
        '/<div class="Lyrics__Container[^"]*"[^>]*>(.*?)<\/div>/si',
        $html,
        $matches
    );

    if (empty($matches[1])) {
        // thử fallback cũ
        preg_match_all(
            '/<div[^>]+data-lyrics-container="true"[^>]*>(.*?)<\/div>/si',
            $html,
            $matches
        );
    }

    if (empty($matches[1])) {
        return ["success" => false, "error" => "Không trích xuất được lyrics từ HTML."];
    }

    $lyrics_html = implode("\n", $matches[1]);

    /* ============================================================
       STEP 4 — Convert <br> thành xuống dòng thật
    ============================================================ */
    $lyrics_html = preg_replace('/<br\s*\/?>/i', "\n", $lyrics_html);

    /* ============================================================
       STEP 5 — Remove HTML tags
    ============================================================ */
    $lyrics = strip_tags($lyrics_html);

    // Decode HTML entities (&amp; → &, &#39; → ')
    $lyrics = html_entity_decode($lyrics, ENT_QUOTES, 'UTF-8');

    // Clean empty lines
    $lyrics = preg_replace("/\n{3,}/", "\n\n", $lyrics);

    $lyrics = trim($lyrics);

    if ($lyrics === "") {
        return ["success" => false, "error" => "Không tìm thấy nội dung lyrics sau khi parse."];
    }

    return [
        "success" => true,
        "source"  => "genius",
        "lyrics"  => $lyrics
    ];
}


/* ============================================================
   CURL helper function
============================================================ */
function curl_request($url, $headers = [])
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $body = curl_exec($ch);
    $err  = curl_error($ch);

    curl_close($ch);

    return [
        "body"  => $body,
        "error" => $err
    ];
}

?>
