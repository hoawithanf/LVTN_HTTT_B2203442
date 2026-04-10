<?php

include_once __DIR__ . "/genius_api.php";

function nln_lyrics_save_cache($conn, $song_id, $lyrics)
{
    if (empty($song_id) || trim((string) $lyrics) === '') {
        return;
    }

    $stmt = $conn->prepare("UPDATE songs SET lyrics = ? WHERE song_id = ?");
    $stmt->bind_param("si", $lyrics, $song_id);
    $stmt->execute();
    $stmt->close();
}

function nln_lyrics_request($url, $headers = [])
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => "Mozilla/5.0",
    ]);

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return [
        'body' => $body,
        'code' => $code,
        'error' => $error,
    ];
}

function nln_lyrics_title_variants($title)
{
    $title = trim((string) $title);
    $variants = [];

    if ($title !== '') {
        $variants[] = $title;
    }

    $normalized = preg_replace('/\s+/', ' ', $title);
    $normalized = preg_replace('/\((.*?)\)/', '', $normalized);
    $normalized = preg_replace('/\[(.*?)\]/', '', $normalized);
    $normalized = preg_replace('/\b(feat|ft)\.?\s+.+$/i', '', $normalized);
    $normalized = preg_replace('/\s+-\s+.+$/', '', $normalized);
    $normalized = trim(preg_replace('/\s+/', ' ', $normalized));

    if ($normalized !== '' && !in_array($normalized, $variants, true)) {
        $variants[] = $normalized;
    }

    $stripped = preg_replace('/[^a-zA-Z0-9\s]/', '', $normalized);
    $stripped = trim(preg_replace('/\s+/', ' ', $stripped));
    if ($stripped !== '' && !in_array($stripped, $variants, true)) {
        $variants[] = $stripped;
    }

    return $variants;
}

function nln_fetch_lyrics_ovh($artist, $titleVariants)
{
    $artist = trim((string) $artist);
    if ($artist === '') {
        return ['success' => false];
    }

    foreach ($titleVariants as $variant) {
        $url = "https://api.lyrics.ovh/v1/" . rawurlencode($artist) . "/" . rawurlencode($variant);
        $response = nln_lyrics_request($url);

        if ($response['error'] || $response['code'] !== 200) {
            continue;
        }

        $data = json_decode((string) $response['body'], true);
        $lyrics = trim((string) ($data['lyrics'] ?? ''));
        if ($lyrics !== '') {
            return [
                'success' => true,
                'source' => 'lyrics.ovh',
                'lyrics' => $lyrics,
            ];
        }
    }

    return ['success' => false];
}

function fetchLyricsAndCache($conn, $artist, $title, $song_id = null)
{
    $artist_trim = trim((string) $artist);
    $title_trim = trim((string) $title);

    if ($title_trim === '') {
        return ['success' => false, 'error' => 'Thiếu title'];
    }

    if (!empty($song_id)) {
        $stmt = $conn->prepare("SELECT lyrics FROM songs WHERE song_id = ?");
        $stmt->bind_param("i", $song_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if (!empty($row['lyrics'])) {
            return [
                'success' => true,
                'source' => 'database',
                'lyrics' => $row['lyrics'],
            ];
        }
    }

    $titleVariants = nln_lyrics_title_variants($title_trim);

    $ovh = nln_fetch_lyrics_ovh($artist_trim, $titleVariants);
    if (!empty($ovh['success'])) {
        nln_lyrics_save_cache($conn, $song_id, $ovh['lyrics']);
        return $ovh;
    }

    $geniusToken = trim((string) getenv("GENIUS_TOKEN"));
    if ($geniusToken !== '') {
        foreach ($titleVariants as $variant) {
            $genius = genius_get_lyrics($artist_trim, $variant, $geniusToken);
            if (!empty($genius['success']) && !empty($genius['lyrics'])) {
                nln_lyrics_save_cache($conn, $song_id, $genius['lyrics']);
                return [
                    'success' => true,
                    'source' => 'genius',
                    'lyrics' => $genius['lyrics'],
                ];
            }
        }
    }

    return [
        'success' => false,
        'error' => 'Không tìm thấy lyrics từ dữ liệu cục bộ hoặc các nguồn dự phòng.',
    ];
}
