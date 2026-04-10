<?php

require_once __DIR__ . '/song_helpers.php';
require_once __DIR__ . '/recommendation_helpers.php';
require_once __DIR__ . '/openai_helpers.php';
require_once __DIR__ . '/../public/includes/env_loader.php';

function nln_persona_load_env(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }

    loadEnv(dirname(__DIR__) . '/.env');
    $loaded = true;
}

function nln_persona_fetch_one(mysqli_stmt $stmt): array
{
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result) {
        return [];
    }

    $row = $result->fetch_assoc();
    return is_array($row) ? $row : [];
}

function nln_persona_fetch_all(mysqli_stmt $stmt): array
{
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result) {
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}

function nln_persona_behavior_stats(mysqli $conn, int $userId): array
{
    $stats = [
        'total_searches' => 0,
        'unique_songs' => 0,
        'followed_artists' => 0,
        'favorite_albums' => 0,
        'repeat_songs' => 0,
        'discovery_songs' => 0,
        'artist_focus_ratio' => 0,
    ];

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total_searches, COUNT(DISTINCT song_id) AS unique_songs
        FROM search_logs
        WHERE user_id = ?
    ");
    $stmt->bind_param('i', $userId);
    $row = nln_persona_fetch_one($stmt);
    $stmt->close();
    if ($row) {
        $stats['total_searches'] = (int) ($row['total_searches'] ?? 0);
        $stats['unique_songs'] = (int) ($row['unique_songs'] ?? 0);
    }

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM artist_follows WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $row = nln_persona_fetch_one($stmt);
    $stmt->close();
    $stats['followed_artists'] = (int) ($row['total'] ?? 0);

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM album_favorites WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $row = nln_persona_fetch_one($stmt);
    $stmt->close();
    $stats['favorite_albums'] = (int) ($row['total'] ?? 0);

    $stmt = $conn->prepare("
        SELECT
            SUM(CASE WHEN per_song.total_hits >= 2 THEN 1 ELSE 0 END) AS repeat_songs,
            SUM(CASE WHEN per_song.total_hits = 1 THEN 1 ELSE 0 END) AS discovery_songs
        FROM (
            SELECT song_id, COUNT(*) AS total_hits
            FROM search_logs
            WHERE user_id = ?
            GROUP BY song_id
        ) AS per_song
    ");
    $stmt->bind_param('i', $userId);
    $row = nln_persona_fetch_one($stmt);
    $stmt->close();
    $stats['repeat_songs'] = (int) ($row['repeat_songs'] ?? 0);
    $stats['discovery_songs'] = (int) ($row['discovery_songs'] ?? 0);

    return $stats;
}

function nln_build_user_music_profile(mysqli $conn, int $userId): array
{
    $profile = nln_recommendation_user_profile($conn, $userId);
    $stats = nln_persona_behavior_stats($conn, $userId);

    $topArtistWeight = isset($profile['top_artists'][0]['weight']) ? (int) $profile['top_artists'][0]['weight'] : 0;
    $stats['artist_focus_ratio'] = $stats['total_searches'] > 0
        ? round($topArtistWeight / $stats['total_searches'], 4)
        : 0;

    $profile['behavior'] = $stats;
    $profile['top_artist'] = $profile['top_artists'][0] ?? [];
    $profile['top_genre'] = $profile['top_genres'][0] ?? [];
    $profile['top_album'] = $profile['top_albums'][0] ?? [];

    return $profile;
}

function nln_persona_local_signals(array $profile): array
{
    $behavior = $profile['behavior'] ?? [];
    $topGenre = $profile['top_genre'] ?? [];
    $topArtist = $profile['top_artist'] ?? [];
    $topAlbum = $profile['top_album'] ?? [];

    $totalSearches = (int) ($behavior['total_searches'] ?? 0);
    $repeatSongs = (int) ($behavior['repeat_songs'] ?? 0);
    $discoverySongs = (int) ($behavior['discovery_songs'] ?? 0);
    $followedArtists = (int) ($behavior['followed_artists'] ?? 0);
    $favoriteAlbums = (int) ($behavior['favorite_albums'] ?? 0);
    $artistFocusRatio = (float) ($behavior['artist_focus_ratio'] ?? 0);

    $topGenreName = (string) ($topGenre['genre_name'] ?? '');
    $topArtistName = (string) ($topArtist['artist_name'] ?? '');
    $topArtistWeight = (int) ($topArtist['weight'] ?? 0);
    $topAlbumWeight = (int) ($topAlbum['weight'] ?? 0);

    return [
        'total_searches' => $totalSearches,
        'repeat_songs' => $repeatSongs,
        'discovery_songs' => $discoverySongs,
        'followed_artists' => $followedArtists,
        'favorite_albums' => $favoriteAlbums,
        'artist_focus_ratio' => $artistFocusRatio,
        'top_genre_name' => $topGenreName,
        'top_artist_name' => $topArtistName,
        'top_artist_weight' => $topArtistWeight,
        'top_album_weight' => $topAlbumWeight,
        'is_repeat_heavy' => $repeatSongs > $discoverySongs,
        'is_discovery_heavy' => $discoverySongs > $repeatSongs,
        'is_pop_leaning' => mb_strtolower($topGenreName, 'UTF-8') === 'pop',
    ];
}

function nln_classify_music_persona_local(array $profile): array
{
    $signals = nln_persona_local_signals($profile);
    $topGenreName = $signals['top_genre_name'] !== '' ? $signals['top_genre_name'] : 'gu nhạc riêng';
    $topArtistName = $signals['top_artist_name'] !== '' ? $signals['top_artist_name'] : 'một nghệ sĩ nổi bật';

    if ($signals['artist_focus_ratio'] >= 0.55 || ($signals['followed_artists'] >= 3 && $signals['artist_focus_ratio'] >= 0.35)) {
        return [
            'persona_key' => 'artist_loyal_fan',
            'persona_title' => 'Artist-Loyal Fan',
            'persona_description' => 'Bạn có xu hướng quay lại một nhóm nghệ sĩ quen thuộc và xây gu nghe xoay quanh các tên tuổi mình quan tâm nhất.',
            'reasons' => [
                'Lịch sử tìm kiếm tập trung mạnh vào ' . $topArtistName . '.',
                'Bạn có xu hướng duy trì sự quan tâm dài hạn qua theo dõi nghệ sĩ.',
                'Mức độ lặp lại bài hát cao hơn xu hướng khám phá mới.',
            ],
        ];
    }

    if ($signals['favorite_albums'] >= 4 || $signals['top_album_weight'] >= 10) {
        return [
            'persona_key' => 'album_explorer',
            'persona_title' => 'Album Explorer',
            'persona_description' => 'Bạn không chỉ quan tâm từng bài hát riêng lẻ mà còn có xu hướng quay lại các album như một trải nghiệm trọn vẹn.',
            'reasons' => [
                'Số album yêu thích của bạn tương đối cao.',
                'Một số album xuất hiện lặp lại rõ rệt trong lịch sử tìm kiếm.',
                'Hành vi của bạn cho thấy xu hướng khám phá theo cụm nội dung thay vì chỉ theo single.',
            ],
        ];
    }

    if ($signals['is_pop_leaning'] && !$signals['is_discovery_heavy']) {
        return [
            'persona_key' => 'story_driven_pop_listener',
            'persona_title' => 'Story-Driven Pop Listener',
            'persona_description' => 'Gu nghe của bạn nghiêng về các ca khúc pop có câu chuyện, cảm xúc rõ và dễ tạo kết nối cá nhân qua lyrics lẫn concept album.',
            'reasons' => [
                'Pop là thể loại nổi bật nhất trong hành vi gần đây của bạn.',
                'Bạn có xu hướng quay lại các bài hát quen thuộc thay vì chỉ lướt qua một lần.',
                'Nghệ sĩ nổi bật hiện tại là ' . $topArtistName . '.',
            ],
        ];
    }

    if ($signals['is_discovery_heavy'] && $signals['followed_artists'] <= 2) {
        return [
            'persona_key' => 'balanced_music_explorer',
            'persona_title' => 'Balanced Music Explorer',
            'persona_description' => 'Bạn có xu hướng mở rộng vùng nghe, thử thêm nhiều bài hát mới và chưa bị cố định hoàn toàn vào một nghệ sĩ hay một album.',
            'reasons' => [
                'Số bài hát khám phá mới đang nhỉnh hơn số bài quay lại.',
                'Mức độ tập trung vào một nghệ sĩ chưa quá áp đảo.',
                'Lịch sử của bạn cho thấy gu nghe tương đối mở và linh hoạt.',
            ],
        ];
    }

    return [
        'persona_key' => 'chart_focused_listener',
        'persona_title' => 'Chart-Focused Listener',
        'persona_description' => 'Bạn thiên về các bài hát đang được quan tâm mạnh, dễ tạo cảm giác bắt nhịp xu hướng và giữ playlist luôn cập nhật.',
        'reasons' => [
            'Lịch sử quan tâm của bạn tập trung vào các bài hát có độ phổ biến cao.',
            'Thể loại nổi trội hiện tại là ' . $topGenreName . '.',
            'Gu nghe của bạn cân bằng giữa quay lại bài quen thuộc và cập nhật nội dung mới.',
        ],
    ];
}

function nln_persona_openai_enrich(array $profile, array $persona, array $insightCards): array
{
    nln_persona_load_env();

    if (nln_openai_api_key() === '') {
        return ['success' => false, 'error' => 'missing_api_key'];
    }

    $compactProfile = [
        'top_artist' => [
            'artist_name' => $profile['top_artist']['artist_name'] ?? '',
            'weight' => (int) ($profile['top_artist']['weight'] ?? 0),
        ],
        'top_genre' => [
            'genre_name' => $profile['top_genre']['genre_name'] ?? '',
            'weight' => (int) ($profile['top_genre']['weight'] ?? 0),
        ],
        'top_album' => [
            'album_name' => $profile['top_album']['album_name'] ?? '',
            'weight' => (int) ($profile['top_album']['weight'] ?? 0),
        ],
        'top_artists' => array_map(static function ($item) {
            return [
                'artist_name' => (string) ($item['artist_name'] ?? ''),
                'weight' => (int) ($item['weight'] ?? 0),
            ];
        }, array_slice($profile['top_artists'] ?? [], 0, 4)),
        'top_genres' => array_map(static function ($item) {
            return [
                'genre_name' => (string) ($item['genre_name'] ?? ''),
                'weight' => (int) ($item['weight'] ?? 0),
            ];
        }, array_slice($profile['top_genres'] ?? [], 0, 4)),
        'behavior' => [
            'total_searches' => (int) ($profile['behavior']['total_searches'] ?? 0),
            'unique_songs' => (int) ($profile['behavior']['unique_songs'] ?? 0),
            'repeat_songs' => (int) ($profile['behavior']['repeat_songs'] ?? 0),
            'discovery_songs' => (int) ($profile['behavior']['discovery_songs'] ?? 0),
            'followed_artists' => (int) ($profile['behavior']['followed_artists'] ?? 0),
            'favorite_albums' => (int) ($profile['behavior']['favorite_albums'] ?? 0),
            'artist_focus_ratio' => (float) ($profile['behavior']['artist_focus_ratio'] ?? 0),
        ],
        'recent_searches' => array_map(static function ($item) {
            return [
                'song_title' => (string) ($item['song_title'] ?? ''),
                'artist_name' => (string) ($item['artist_name'] ?? ''),
            ];
        }, array_slice($profile['recent_searches'] ?? [], 0, 6)),
    ];

    $input = json_encode([
        'persona_key' => (string) ($persona['persona_key'] ?? ''),
        'persona_title' => (string) ($persona['persona_title'] ?? ''),
        'local_description' => (string) ($persona['persona_description'] ?? ''),
        'local_reasons' => array_values($persona['reasons'] ?? []),
        'insight_cards' => array_map(static function ($card) {
            return [
                'label' => (string) ($card['label'] ?? ''),
                'value' => (string) ($card['value'] ?? ''),
                'note' => (string) ($card['note'] ?? ''),
            ];
        }, $insightCards),
        'user_profile' => $compactProfile,
    ], JSON_UNESCAPED_UNICODE);

    $instructions = "Ban dang viet lai noi dung cho trang AI Music Persona cua NLN Lyrics. "
        . "Tuyet doi khong thay doi persona_key hoac persona_title da duoc chot san. "
        . "Chi duoc dien giai dua tren du lieu da cung cap. "
        . "Khong duoc tao them nghe si, album, bai hat, the loai, so lieu, hoac ket luan tam ly ngoai du lieu. "
        . "Hay viet tu nhien, gon, ro, bang tieng Viet. "
        . "Tra ve JSON hop le theo schema: "
        . "{\"persona_description\":\"...\",\"why_this_persona\":[\"...\",\"...\",\"...\"],\"insight_notes\":[\"...\",\"...\",\"...\"]}. "
        . "Mang insight_notes phai theo dung thu tu cua insight_cards da duoc cung cap. "
        . "Khong them markdown, khong giai thich ngoai JSON.";

    $result = nln_openai_text_response($instructions, $input, [
        'max_output_tokens' => 420,
    ]);

    if (empty($result['success']) || empty($result['text'])) {
        return ['success' => false, 'error' => $result['error'] ?? 'openai_failed'];
    }

    $json = trim((string) $result['text']);
    $json = preg_replace('/^```json\s*|\s*```$/', '', $json);
    $data = json_decode($json, true);

    if (!is_array($data)) {
        return ['success' => false, 'error' => 'invalid_json'];
    }

    $description = trim((string) ($data['persona_description'] ?? ''));
    $why = array_values(array_filter(array_map('trim', (array) ($data['why_this_persona'] ?? []))));
    $insightNotes = array_values(array_filter(array_map('trim', (array) ($data['insight_notes'] ?? []))));

    if ($description === '') {
        return ['success' => false, 'error' => 'missing_description'];
    }

    return [
        'success' => true,
        'persona_description' => $description,
        'why_this_persona' => array_slice($why, 0, 4),
        'insight_notes' => $insightNotes,
        'model' => $result['model'] ?? '',
    ];
}

function nln_persona_fetch_artist_candidates(mysqli $conn, array $profile, int $limit = 4): array
{
    $followedArtistIds = array_map('intval', array_column($profile['followed_artists'] ?? [], 'artist_id'));
    $excludeIds = array_values(array_unique($followedArtistIds));

    $topGenreIds = array_values(array_filter(array_map('intval', array_column($profile['top_genres'] ?? [], 'genre_id'))));

    $buildSql = static function (bool $withGenreFilter, array $excludeIds, array $topGenreIds): array {
        $sql = "
        SELECT
            ar.artist_id,
            ar.artist_name,
            ar.avatar,
            COUNT(s.song_id) AS song_count,
            COUNT(sl.log_id) AS popularity
        FROM artists ar
        LEFT JOIN songs s ON s.artist_id = ar.artist_id
        LEFT JOIN search_logs sl ON sl.song_id = s.song_id
        WHERE 1=1
    ";

        $types = '';
        $params = [];

        if (!empty($excludeIds)) {
            $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
            $sql .= " AND ar.artist_id NOT IN ($placeholders)";
            $types .= str_repeat('i', count($excludeIds));
            $params = array_merge($params, $excludeIds);
        }

        if ($withGenreFilter && !empty($topGenreIds)) {
            $placeholders = implode(',', array_fill(0, count($topGenreIds), '?'));
            $sql .= " AND EXISTS (
            SELECT 1
            FROM songs sx
            WHERE sx.artist_id = ar.artist_id
              AND sx.genre_id IN ($placeholders)
        )";
            $types .= str_repeat('i', count($topGenreIds));
            $params = array_merge($params, $topGenreIds);
        }

        $sql .= "
        GROUP BY ar.artist_id, ar.artist_name, ar.avatar
        ORDER BY popularity DESC, song_count DESC, ar.artist_name ASC
        LIMIT ?
    ";

        return [$sql, $types, $params];
    };

    [$sql, $types, $params] = $buildSql(true, $excludeIds, $topGenreIds);
    $types .= 'i';
    $params[] = $limit;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $rows = nln_persona_fetch_all($stmt);
    $stmt->close();

    if (!empty($rows)) {
        return $rows;
    }

    [$sql, $types, $params] = $buildSql(false, $excludeIds, $topGenreIds);
    $types .= 'i';
    $params[] = $limit;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $rows = nln_persona_fetch_all($stmt);
    $stmt->close();

    return $rows;
}

function nln_persona_fetch_album_candidates(mysqli $conn, array $profile, int $limit = 4): array
{
    $favoriteAlbumIds = array_map('intval', array_column($profile['favorite_albums'] ?? [], 'album_id'));
    $topAlbumIds = array_map('intval', array_column($profile['top_albums'] ?? [], 'album_id'));
    $excludeIds = array_values(array_unique(array_merge($favoriteAlbumIds, $topAlbumIds)));
    $topArtistIds = array_values(array_filter(array_map('intval', array_column($profile['top_artists'] ?? [], 'artist_id'))));

    $sql = "
        SELECT
            al.album_id,
            al.album_name,
            al.cover_image,
            ar.artist_id,
            ar.artist_name,
            COUNT(sl.log_id) AS popularity
        FROM albums al
        INNER JOIN artists ar ON ar.artist_id = al.artist_id
        LEFT JOIN songs s ON s.album_id = al.album_id
        LEFT JOIN search_logs sl ON sl.song_id = s.song_id
        WHERE 1=1
    ";

    $types = '';
    $params = [];

    if (!empty($excludeIds)) {
        $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
        $sql .= " AND al.album_id NOT IN ($placeholders)";
        $types .= str_repeat('i', count($excludeIds));
        $params = array_merge($params, $excludeIds);
    }

    if (!empty($topArtistIds)) {
        $placeholders = implode(',', array_fill(0, count($topArtistIds), '?'));
        $sql .= " AND al.artist_id IN ($placeholders)";
        $types .= str_repeat('i', count($topArtistIds));
        $params = array_merge($params, $topArtistIds);
    }

    $sql .= "
        GROUP BY al.album_id, al.album_name, al.cover_image, ar.artist_id, ar.artist_name
        ORDER BY popularity DESC, al.release_year DESC, al.album_name ASC
        LIMIT ?
    ";

    $types .= 'i';
    $params[] = $limit;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $rows = nln_persona_fetch_all($stmt);
    $stmt->close();

    return $rows;
}

function nln_persona_song_recommendations(mysqli $conn, int $userId, bool $refresh = false, int $limit = 4): array
{
    $result = nln_profile_recommendations_fresh($conn, $userId, $limit, $refresh);
    return $result['items'] ?? [];
}

function nln_get_user_persona(mysqli $conn, int $userId, bool $refresh = false): array
{
    $profile = nln_build_user_music_profile($conn, $userId);
    $behavior = $profile['behavior'] ?? [];

    if ((int) ($behavior['total_searches'] ?? 0) <= 0) {
        return [
            'has_enough_data' => false,
            'profile' => $profile,
            'persona' => [],
            'insight_cards' => [],
            'why_this_persona' => [],
            'recommendations' => [
                'songs' => [],
                'artists' => [],
                'albums' => [],
            ],
        ];
    }

    $persona = nln_classify_music_persona_local($profile);
    $topGenreName = (string) ($profile['top_genre']['genre_name'] ?? 'Chưa xác định');
    $topArtistName = (string) ($profile['top_artist']['artist_name'] ?? 'Chưa xác định');
    $repeatSongs = (int) ($behavior['repeat_songs'] ?? 0);
    $discoverySongs = (int) ($behavior['discovery_songs'] ?? 0);

    $insightCards = [
        [
            'label' => 'Thể loại nổi trội',
            'value' => $topGenreName,
            'note' => 'Thể loại xuất hiện nhiều nhất trong lịch sử quan tâm của bạn.',
        ],
        [
            'label' => 'Nghệ sĩ nổi bật',
            'value' => $topArtistName,
            'note' => 'Tên tuổi xuất hiện dày nhất trong hành vi tìm kiếm gần đây.',
        ],
        [
            'label' => 'Xu hướng nghe',
            'value' => $repeatSongs > $discoverySongs ? 'Thiên về quay lại' : ($discoverySongs > $repeatSongs ? 'Thiên về khám phá' : 'Khá cân bằng'),
            'note' => 'So sánh giữa số bài quay lại nhiều lần và số bài chỉ chạm tới một lần.',
        ],
        [
            'label' => 'Tập trung nghệ sĩ',
            'value' => number_format(((float) ($behavior['artist_focus_ratio'] ?? 0)) * 100, 0) . '%',
            'note' => 'Tỷ trọng quan tâm dồn vào nghệ sĩ nổi bật nhất của bạn.',
        ],
    ];

    $copySource = 'local';
    $openAiCopy = nln_persona_openai_enrich($profile, $persona, $insightCards);
    if (!empty($openAiCopy['success'])) {
        $persona['persona_description'] = $openAiCopy['persona_description'];

        if (!empty($openAiCopy['why_this_persona'])) {
            $persona['reasons'] = $openAiCopy['why_this_persona'];
        }

        foreach ($insightCards as $index => $card) {
            if (!empty($openAiCopy['insight_notes'][$index])) {
                $insightCards[$index]['note'] = $openAiCopy['insight_notes'][$index];
            }
        }

        $copySource = 'openai';
    }

    return [
        'has_enough_data' => true,
        'profile' => $profile,
        'persona' => $persona,
        'insight_cards' => $insightCards,
        'why_this_persona' => $persona['reasons'] ?? [],
        'copy_source' => $copySource,
        'recommendations' => [
            'songs' => nln_persona_song_recommendations($conn, $userId, $refresh, 4),
            'artists' => nln_persona_fetch_artist_candidates($conn, $profile, 4),
            'albums' => nln_persona_fetch_album_candidates($conn, $profile, 4),
        ],
    ];
}
