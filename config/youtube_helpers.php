<?php

require_once __DIR__ . '/../public/includes/env_loader.php';

function nln_youtube_load_env(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }

    loadEnv(dirname(__DIR__) . '/.env');
    $loaded = true;
}

function nln_youtube_api_key(): string
{
    nln_youtube_load_env();
    return trim((string) getenv('YOUTUBE_API_KEY'));
}

function nln_youtube_request(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'NLN-Lyrics/1.0',
    ]);

    $body = curl_exec($ch);
    $error = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'body' => $body,
        'error' => $error,
        'code' => $code,
    ];
}

function nln_youtube_is_good_video(array $item): bool
{
    $status = $item['status'] ?? [];
    if (empty($status['embeddable']) || ($status['privacyStatus'] ?? '') !== 'public') {
        return false;
    }

    $title = strtolower((string) ($item['snippet']['title'] ?? ''));
    $blockedTerms = ['reaction', 'karaoke', 'cover', 'sped up', 'slowed', '8d', 'fanmade'];
    foreach ($blockedTerms as $term) {
        if (strpos($title, $term) !== false) {
            return false;
        }
    }

    return true;
}

function nln_youtube_pick_best_video(array $items): ?string
{
    $preferredTerms = ['official video', 'official music video', 'lyrics', 'lyric video', 'official audio', 'audio'];

    foreach ($preferredTerms as $term) {
        foreach ($items as $item) {
            if (!nln_youtube_is_good_video($item)) {
                continue;
            }

            $title = strtolower((string) ($item['snippet']['title'] ?? ''));
            if (strpos($title, $term) !== false) {
                return (string) ($item['id'] ?? '');
            }
        }
    }

    foreach ($items as $item) {
        if (nln_youtube_is_good_video($item)) {
            return (string) ($item['id'] ?? '');
        }
    }

    return null;
}

function nln_youtube_validate_video_id(string $videoId): bool
{
    $videoId = trim($videoId);
    if ($videoId === '' || !preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId)) {
        return false;
    }

    $apiKey = nln_youtube_api_key();
    if ($apiKey === '') {
        // Without an API key we cannot verify embeddability,
        // but a syntactically valid saved ID should still be usable.
        return true;
    }

    $url = 'https://www.googleapis.com/youtube/v3/videos?' . http_build_query([
        'part' => 'status,snippet',
        'id' => $videoId,
        'key' => $apiKey,
    ]);

    $response = nln_youtube_request($url);
    if ($response['error'] || $response['code'] !== 200) {
        return false;
    }

    $data = json_decode((string) $response['body'], true);
    if (empty($data['items'][0])) {
        return false;
    }

    return nln_youtube_is_good_video($data['items'][0]);
}

function nln_youtube_find_video_id(string $title, string $artist): ?string
{
    $apiKey = nln_youtube_api_key();
    if ($apiKey === '') {
        return null;
    }

    $query = trim($title . ' ' . $artist);
    if ($query === '') {
        return null;
    }

    $searchUrl = 'https://www.googleapis.com/youtube/v3/search?' . http_build_query([
        'part' => 'snippet',
        'q' => $query,
        'type' => 'video',
        'videoEmbeddable' => 'true',
        'videoSyndicated' => 'true',
        'videoCategoryId' => '10',
        'maxResults' => 8,
        'key' => $apiKey,
    ]);

    $searchResponse = nln_youtube_request($searchUrl);
    if ($searchResponse['error'] || $searchResponse['code'] !== 200) {
        return null;
    }

    $searchData = json_decode((string) $searchResponse['body'], true);
    $videoIds = [];
    foreach (($searchData['items'] ?? []) as $item) {
        $id = (string) ($item['id']['videoId'] ?? '');
        if ($id !== '') {
            $videoIds[] = $id;
        }
    }

    if (empty($videoIds)) {
        return null;
    }

    $videosUrl = 'https://www.googleapis.com/youtube/v3/videos?' . http_build_query([
        'part' => 'status,snippet',
        'id' => implode(',', $videoIds),
        'key' => $apiKey,
    ]);

    $videosResponse = nln_youtube_request($videosUrl);
    if ($videosResponse['error'] || $videosResponse['code'] !== 200) {
        return null;
    }

    $videosData = json_decode((string) $videosResponse['body'], true);
    return nln_youtube_pick_best_video($videosData['items'] ?? []);
}

function nln_youtube_resolve_video_id(mysqli $conn, int $songId, string $title, string $artist, ?string $currentVideoId): ?string
{
    $currentVideoId = trim((string) $currentVideoId);
    if ($currentVideoId !== '' && nln_youtube_validate_video_id($currentVideoId)) {
        return $currentVideoId;
    }

    $newVideoId = nln_youtube_find_video_id($title, $artist);
    if ($newVideoId === null) {
        return null;
    }

    $stmt = $conn->prepare("UPDATE songs SET youtube_video_id = ? WHERE song_id = ?");
    $stmt->bind_param("si", $newVideoId, $songId);
    $stmt->execute();
    $stmt->close();

    return $newVideoId;
}
