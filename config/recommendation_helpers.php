<?php

require_once __DIR__ . '/song_helpers.php';
require_once __DIR__ . '/openai_helpers.php';
require_once __DIR__ . '/../public/includes/env_loader.php';

function nln_recommendation_load_env(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }

    loadEnv(dirname(__DIR__) . '/.env');
    $loaded = true;
}

function nln_recommendation_fetch_cached(mysqli $conn, int $userId, int $limit = 6, int $maxAgeHours = 12): array
{
    $stmt = $conn->prepare("
        SELECT s.song_id, s.title, ar.artist_name,
               s.cover_image AS song_cover, al.cover_image AS album_cover,
               ur.base_score, ur.updated_at
        FROM user_recommendations ur
        JOIN songs s ON ur.song_id = s.song_id
        JOIN artists ar ON s.artist_id = ar.artist_id
        LEFT JOIN albums al ON s.album_id = al.album_id
        WHERE ur.user_id = ?
          AND ur.updated_at >= (NOW() - INTERVAL ? HOUR)
        ORDER BY ur.base_score DESC, ur.updated_at DESC
        LIMIT ?
    ");
    $stmt->bind_param("iii", $userId, $maxAgeHours, $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($rows as &$row) {
        $row['cover'] = nln_public_song_cover_path($row['song_cover'] ?? null, $row['album_cover'] ?? null);
    }
    unset($row);

    return $rows;
}

function nln_recommendation_recent_song_ids(mysqli $conn, int $userId, int $limit = 12): array
{
    $stmt = $conn->prepare("
        SELECT song_id
        FROM user_recommendations
        WHERE user_id = ?
        ORDER BY updated_at DESC, base_score DESC
        LIMIT ?
    ");
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return array_map('intval', array_column($rows, 'song_id'));
}

function nln_recommendation_clear_cache(mysqli $conn, int $userId): void
{
    $stmt = $conn->prepare("DELETE FROM user_recommendations WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
}

function nln_recommendation_user_profile(mysqli $conn, int $userId): array
{
    $profile = [
        'recent_searches' => [],
        'top_artists' => [],
        'top_genres' => [],
        'top_albums' => [],
        'followed_artists' => [],
        'favorite_albums' => [],
        'heard_song_ids' => [],
    ];

    $stmtRecent = $conn->prepare("
        SELECT sl.song_title, sl.artist_name, sl.search_time
        FROM search_logs sl
        WHERE sl.user_id = ?
        ORDER BY sl.search_time DESC
        LIMIT 8
    ");
    $stmtRecent->bind_param("i", $userId);
    $stmtRecent->execute();
    $profile['recent_searches'] = $stmtRecent->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtRecent->close();

    $stmtArtists = $conn->prepare("
        SELECT s.artist_id, ar.artist_name, COUNT(*) AS weight
        FROM search_logs sl
        JOIN songs s ON sl.song_id = s.song_id
        JOIN artists ar ON s.artist_id = ar.artist_id
        WHERE sl.user_id = ?
        GROUP BY s.artist_id, ar.artist_name
        ORDER BY weight DESC, ar.artist_name ASC
        LIMIT 5
    ");
    $stmtArtists->bind_param("i", $userId);
    $stmtArtists->execute();
    $profile['top_artists'] = $stmtArtists->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtArtists->close();

    $stmtGenres = $conn->prepare("
        SELECT g.genre_id, g.genre_name, COUNT(*) AS weight
        FROM search_logs sl
        JOIN songs s ON sl.song_id = s.song_id
        LEFT JOIN genres g ON s.genre_id = g.genre_id
        WHERE sl.user_id = ? AND s.genre_id IS NOT NULL
        GROUP BY g.genre_id, g.genre_name
        ORDER BY weight DESC, g.genre_name ASC
        LIMIT 5
    ");
    $stmtGenres->bind_param("i", $userId);
    $stmtGenres->execute();
    $profile['top_genres'] = $stmtGenres->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtGenres->close();

    $stmtAlbums = $conn->prepare("
        SELECT s.album_id, al.album_name, COUNT(*) AS weight
        FROM search_logs sl
        JOIN songs s ON sl.song_id = s.song_id
        JOIN albums al ON s.album_id = al.album_id
        WHERE sl.user_id = ? AND s.album_id IS NOT NULL
        GROUP BY s.album_id, al.album_name
        ORDER BY weight DESC, al.album_name ASC
        LIMIT 5
    ");
    $stmtAlbums->bind_param("i", $userId);
    $stmtAlbums->execute();
    $profile['top_albums'] = $stmtAlbums->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtAlbums->close();

    $stmtFollowed = $conn->prepare("
        SELECT af.artist_id, ar.artist_name
        FROM artist_follows af
        JOIN artists ar ON af.artist_id = ar.artist_id
        WHERE af.user_id = ?
        ORDER BY af.created_at DESC
        LIMIT 8
    ");
    $stmtFollowed->bind_param("i", $userId);
    $stmtFollowed->execute();
    $profile['followed_artists'] = $stmtFollowed->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtFollowed->close();

    $stmtFavAlbums = $conn->prepare("
        SELECT af.album_id, al.album_name
        FROM album_favorites af
        JOIN albums al ON af.album_id = al.album_id
        WHERE af.user_id = ?
        ORDER BY af.created_at DESC
        LIMIT 8
    ");
    $stmtFavAlbums->bind_param("i", $userId);
    $stmtFavAlbums->execute();
    $profile['favorite_albums'] = $stmtFavAlbums->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtFavAlbums->close();

    $stmtHeard = $conn->prepare("
        SELECT DISTINCT song_id
        FROM search_logs
        WHERE user_id = ?
    ");
    $stmtHeard->bind_param("i", $userId);
    $stmtHeard->execute();
    $profile['heard_song_ids'] = array_map('intval', array_column($stmtHeard->get_result()->fetch_all(MYSQLI_ASSOC), 'song_id'));
    $stmtHeard->close();

    return $profile;
}

function nln_recommendation_query_songs(mysqli $conn, array $excludeSongIds, int $limit, array $filters = []): array
{
    $sql = "
        SELECT s.song_id, s.title, s.release_date,
               ar.artist_name, al.album_name, g.genre_name,
               s.cover_image AS song_cover, al.cover_image AS album_cover,
               COUNT(sl.log_id) AS popularity
        FROM songs s
        JOIN artists ar ON s.artist_id = ar.artist_id
        LEFT JOIN albums al ON s.album_id = al.album_id
        LEFT JOIN genres g ON s.genre_id = g.genre_id
        LEFT JOIN search_logs sl ON sl.song_id = s.song_id
        WHERE 1=1
    ";

    $types = '';
    $params = [];

    if (!empty($excludeSongIds)) {
        $placeholders = implode(',', array_fill(0, count($excludeSongIds), '?'));
        $sql .= " AND s.song_id NOT IN ($placeholders)";
        $types .= str_repeat('i', count($excludeSongIds));
        $params = array_merge($params, $excludeSongIds);
    }

    $orParts = [];

    if (!empty($filters['artist_ids'])) {
        $artistIds = array_values(array_unique(array_map('intval', $filters['artist_ids'])));
        $placeholders = implode(',', array_fill(0, count($artistIds), '?'));
        $orParts[] = "s.artist_id IN ($placeholders)";
        $types .= str_repeat('i', count($artistIds));
        $params = array_merge($params, $artistIds);
    }

    if (!empty($filters['genre_ids'])) {
        $genreIds = array_values(array_unique(array_map('intval', $filters['genre_ids'])));
        $placeholders = implode(',', array_fill(0, count($genreIds), '?'));
        $orParts[] = "s.genre_id IN ($placeholders)";
        $types .= str_repeat('i', count($genreIds));
        $params = array_merge($params, $genreIds);
    }

    if (!empty($filters['album_ids'])) {
        $albumIds = array_values(array_unique(array_map('intval', $filters['album_ids'])));
        $placeholders = implode(',', array_fill(0, count($albumIds), '?'));
        $orParts[] = "s.album_id IN ($placeholders)";
        $types .= str_repeat('i', count($albumIds));
        $params = array_merge($params, $albumIds);
    }

    if (!empty($orParts)) {
        $sql .= " AND (" . implode(' OR ', $orParts) . ")";
    }

    $sql .= "
        GROUP BY s.song_id, s.title, s.release_date, ar.artist_name, al.album_name, g.genre_name, s.cover_image, al.cover_image
        ORDER BY popularity DESC, s.release_date DESC, s.song_id DESC
        LIMIT ?
    ";
    $types .= 'i';
    $params[] = $limit;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($rows as &$row) {
        $row['popularity'] = (int) ($row['popularity'] ?? 0);
        $row['cover'] = nln_public_song_cover_path($row['song_cover'] ?? null, $row['album_cover'] ?? null);
    }
    unset($row);

    return $rows;
}

function nln_recommendation_build_candidates(mysqli $conn, array $profile, int $limit = 18): array
{
    $artistIds = array_map('intval', array_column($profile['top_artists'], 'artist_id'));
    $genreIds = array_map('intval', array_column($profile['top_genres'], 'genre_id'));
    $albumIds = array_map('intval', array_column($profile['top_albums'], 'album_id'));
    $followedArtistIds = array_map('intval', array_column($profile['followed_artists'], 'artist_id'));
    $favoriteAlbumIds = array_map('intval', array_column($profile['favorite_albums'], 'album_id'));
    $heardSongIds = array_map('intval', $profile['heard_song_ids']);

    $rows = nln_recommendation_query_songs($conn, $heardSongIds, $limit, [
        'artist_ids' => array_merge($artistIds, $followedArtistIds),
        'genre_ids' => $genreIds,
        'album_ids' => array_merge($albumIds, $favoriteAlbumIds),
    ]);

    if (count($rows) >= $limit) {
        return array_slice($rows, 0, $limit);
    }

    $extraRows = nln_recommendation_query_songs($conn, $heardSongIds, $limit, []);
    $byId = [];
    foreach (array_merge($rows, $extraRows) as $row) {
        $byId[(int) $row['song_id']] = $row;
    }

    return array_slice(array_values($byId), 0, $limit);
}

function nln_recommendation_rotate_pool(array $items, int $limit = 6, bool $forceRefresh = false): array
{
    if (count($items) <= $limit) {
        return array_slice($items, 0, $limit);
    }

    $poolSize = min(count($items), max($limit * 3, $limit + 4));
    $pool = array_slice($items, 0, $poolSize);

    if ($forceRefresh) {
        shuffle($pool);
        usort($pool, function ($a, $b) {
            $weightedA = ($a['base_score'] ?? 0) + mt_rand(0, 18);
            $weightedB = ($b['base_score'] ?? 0) + mt_rand(0, 18);
            return $weightedB <=> $weightedA;
        });
    }

    return array_slice($pool, 0, $limit);
}

function nln_recommendation_global_fallback(mysqli $conn, array $profile, int $limit = 6): array
{
    $heardSongIds = array_map('intval', $profile['heard_song_ids'] ?? []);

    $rows = nln_recommendation_query_songs($conn, $heardSongIds, max(12, $limit * 2), []);
    if (empty($rows)) {
        $rows = nln_recommendation_query_songs($conn, [], max(12, $limit * 2), []);
    }

    return nln_recommendation_fallback_rank($rows, $profile, max(12, $limit * 3));
}

function nln_recommendation_fallback_rank(array $candidates, array $profile, int $limit = 6): array
{
    $artistWeights = [];
    foreach ($profile['top_artists'] as $artist) {
        $artistWeights[$artist['artist_name']] = (int) $artist['weight'];
    }

    $genreWeights = [];
    foreach ($profile['top_genres'] as $genre) {
        $genreWeights[$genre['genre_name']] = (int) $genre['weight'];
    }

    $albumWeights = [];
    foreach ($profile['top_albums'] as $album) {
        $albumWeights[$album['album_name']] = (int) $album['weight'];
    }

    foreach ($candidates as &$candidate) {
        $score = min(30, (int) ($candidate['popularity'] ?? 0));

        if (!empty($artistWeights[$candidate['artist_name'] ?? ''])) {
            $score += 35 + min(20, $artistWeights[$candidate['artist_name']]);
        }

        if (!empty($genreWeights[$candidate['genre_name'] ?? ''])) {
            $score += 20 + min(10, $genreWeights[$candidate['genre_name']]);
        }

        if (!empty($albumWeights[$candidate['album_name'] ?? ''])) {
            $score += 18 + min(10, $albumWeights[$candidate['album_name']]);
        }

        if (!empty($candidate['release_date'])) {
            $timestamp = strtotime((string) $candidate['release_date']);
            if ($timestamp && $timestamp >= strtotime('-3 years')) {
                $score += 6;
            }
        }

        $candidate['base_score'] = $score;
    }
    unset($candidate);

    usort($candidates, function ($a, $b) {
        $scoreCompare = ($b['base_score'] ?? 0) <=> ($a['base_score'] ?? 0);
        if ($scoreCompare !== 0) {
            return $scoreCompare;
        }

        return ($b['popularity'] ?? 0) <=> ($a['popularity'] ?? 0);
    });

    return array_slice($candidates, 0, $limit);
}

function nln_recommendation_openai_rank(array $profile, array $candidates, int $limit = 6): array
{
    nln_recommendation_load_env();

    if (nln_openai_api_key() === '' || empty($candidates)) {
        return ['success' => false, 'error' => 'OpenAI not configured'];
    }

    $compactProfile = [
        'recent_searches' => array_map(function ($item) {
            return [
                'song_title' => $item['song_title'],
                'artist_name' => $item['artist_name'],
            ];
        }, $profile['recent_searches']),
        'top_artists' => array_map(function ($item) {
            return [
                'artist_name' => $item['artist_name'],
                'weight' => (int) $item['weight'],
            ];
        }, $profile['top_artists']),
        'top_genres' => array_map(function ($item) {
            return [
                'genre_name' => $item['genre_name'],
                'weight' => (int) $item['weight'],
            ];
        }, $profile['top_genres']),
        'top_albums' => array_map(function ($item) {
            return [
                'album_name' => $item['album_name'],
                'weight' => (int) $item['weight'],
            ];
        }, $profile['top_albums']),
        'followed_artists' => array_column($profile['followed_artists'], 'artist_name'),
        'favorite_albums' => array_column($profile['favorite_albums'], 'album_name'),
    ];

    $compactCandidates = array_map(function ($item) {
        return [
            'song_id' => (int) $item['song_id'],
            'title' => $item['title'],
            'artist_name' => $item['artist_name'],
            'album_name' => $item['album_name'],
            'genre_name' => $item['genre_name'],
            'release_date' => $item['release_date'],
            'popularity' => (int) $item['popularity'],
        ];
    }, $candidates);

    $instructions = "Ban la cong cu goi y bai hat cho website NLN Lyrics. "
        . "Chi duoc chon trong danh sach candidate duoc cung cap, khong duoc tao bai hat moi. "
        . "Muc tieu la de xuat bai hat phu hop voi gu cua user dua tren lich su search, nghe, nghe si theo doi va album yeu thich. "
        . "Uu tien bai hat gan gu voi ca si, the loai, album user da quan tam, nhung tranh lap lai bai da nghe neu co the. "
        . "Tra ve duy nhat JSON hop le theo schema: "
        . "{\"recommended_song_ids\":[1,2,3],\"scores\":{\"1\":92,\"2\":88},\"summary\":\"...\"}. "
        . "Khong them markdown, khong giai thich ngoai JSON.";

    $input = json_encode([
        'limit' => $limit,
        'user_profile' => $compactProfile,
        'candidates' => $compactCandidates,
    ], JSON_UNESCAPED_UNICODE);

    $result = nln_openai_text_response($instructions, $input, [
        'max_output_tokens' => 350,
    ]);

    if (empty($result['success']) || empty($result['text'])) {
        return ['success' => false, 'error' => $result['error'] ?? 'OpenAI recommendation failed'];
    }

    $json = trim((string) $result['text']);
    $json = preg_replace('/^```json\s*|\s*```$/', '', $json);
    $data = json_decode($json, true);

    if (!is_array($data) || empty($data['recommended_song_ids']) || !is_array($data['recommended_song_ids'])) {
        return ['success' => false, 'error' => 'OpenAI returned invalid recommendation JSON'];
    }

    $selectedIds = array_values(array_unique(array_map('intval', $data['recommended_song_ids'])));
    $scores = is_array($data['scores'] ?? null) ? $data['scores'] : [];
    $summary = trim((string) ($data['summary'] ?? ''));

    $candidateMap = [];
    foreach ($candidates as $candidate) {
        $candidateMap[(int) $candidate['song_id']] = $candidate;
    }

    $selected = [];
    foreach ($selectedIds as $songId) {
        if (!isset($candidateMap[$songId])) {
            continue;
        }

        $row = $candidateMap[$songId];
        $row['base_score'] = isset($scores[(string) $songId]) ? (float) $scores[(string) $songId] : (float) ($row['popularity'] ?? 0);
        $selected[] = $row;
        if (count($selected) >= $limit) {
            break;
        }
    }

    if (empty($selected)) {
        return ['success' => false, 'error' => 'OpenAI did not select valid candidate songs'];
    }

    return [
        'success' => true,
        'items' => $selected,
        'summary' => $summary,
        'model' => $result['model'] ?? '',
    ];
}

function nln_recommendation_store(mysqli $conn, int $userId, array $items): void
{
    nln_recommendation_clear_cache($conn, $userId);

    if (empty($items)) {
        return;
    }

    $insertStmt = $conn->prepare("
        INSERT INTO user_recommendations (user_id, song_id, base_score, updated_at)
        VALUES (?, ?, ?, NOW())
    ");

    foreach ($items as $item) {
        $songId = (int) ($item['song_id'] ?? 0);
        $score = (float) ($item['base_score'] ?? 0);
        if ($songId <= 0) {
            continue;
        }

        $insertStmt->bind_param("iid", $userId, $songId, $score);
        $insertStmt->execute();
    }

    $insertStmt->close();
}

function nln_profile_recommendations_fresh(mysqli $conn, int $userId, int $limit = 6, bool $forceRefresh = false): array
{
    $recentRecommendationIds = [];
    if ($forceRefresh) {
        $recentRecommendationIds = nln_recommendation_recent_song_ids($conn, $userId, max(12, $limit * 2));
        nln_recommendation_clear_cache($conn, $userId);
    } else {
        $cached = nln_recommendation_fetch_cached($conn, $userId, $limit);
        if (count($cached) >= min(3, $limit)) {
            return [
                'items' => $cached,
                'source' => 'cache',
                'summary' => 'Đã dùng gợi ý đã lưu gần đây.',
            ];
        }
    }

    $profile = nln_recommendation_user_profile($conn, $userId);
    if ($forceRefresh && !empty($recentRecommendationIds)) {
        $profile['heard_song_ids'] = array_values(array_unique(array_merge(
            $profile['heard_song_ids'],
            $recentRecommendationIds
        )));
    }
    $candidates = nln_recommendation_build_candidates($conn, $profile, max(18, $limit * 3));

    if (empty($candidates)) {
        $fallbackItems = nln_recommendation_global_fallback($conn, $profile, $limit);
        if (empty($fallbackItems)) {
            return [
                'items' => [],
                'source' => 'empty',
                'summary' => '',
            ];
        }

        $fallbackItems = nln_recommendation_rotate_pool($fallbackItems, $limit, $forceRefresh);
        nln_recommendation_store($conn, $userId, $fallbackItems);
        return [
            'items' => $fallbackItems,
            'source' => 'global_fallback',
            'summary' => 'Gợi ý phổ biến phù hợp để bạn khám phá thêm.',
        ];
    }

    $openAiResult = nln_recommendation_openai_rank($profile, $candidates, $limit);
    if (!empty($openAiResult['success'])) {
        $items = nln_recommendation_rotate_pool($openAiResult['items'], $limit, $forceRefresh);
        nln_recommendation_store($conn, $userId, $items);

        foreach ($items as &$item) {
            $item['cover'] = nln_public_song_cover_path($item['song_cover'] ?? null, $item['album_cover'] ?? null);
        }
        unset($item);

        return [
            'items' => $items,
            'source' => 'openai',
            'summary' => $openAiResult['summary'] !== '' ? $openAiResult['summary'] : 'Gợi ý dựa trên lịch sử nghe và tìm kiếm gần đây.',
        ];
    }

    $fallbackItems = nln_recommendation_fallback_rank($candidates, $profile, max(12, $limit * 3));
    if (empty($fallbackItems)) {
        $fallbackItems = nln_recommendation_global_fallback($conn, $profile, $limit);
    }
    $fallbackItems = nln_recommendation_rotate_pool($fallbackItems, $limit, $forceRefresh);

    if (empty($fallbackItems)) {
        return [
            'items' => [],
            'source' => 'empty',
            'summary' => '',
        ];
    }

    nln_recommendation_store($conn, $userId, $fallbackItems);
    return [
        'items' => $fallbackItems,
        'source' => 'fallback',
        'summary' => 'Gợi ý dự phòng từ lịch sử nghe, nghệ sĩ và thể loại yêu thích.',
    ];
}

function nln_profile_recommendations(mysqli $conn, int $userId, int $limit = 6, bool $forceRefresh = false): array
{
    return nln_profile_recommendations_fresh($conn, $userId, $limit, $forceRefresh);
}
