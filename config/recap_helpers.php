<?php

function nln_get_recap_period(int $month, int $year): array
{
    $month = max(1, min(12, $month));
    $year = max(2020, min(2100, $year));

    return [
        'month' => $month,
        'year' => $year,
        'label' => 'Tháng ' . $month . '/' . $year,
    ];
}

function nln_recap_fetch_all(mysqli_stmt $stmt): array
{
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}

function nln_recap_fetch_one(mysqli_stmt $stmt): array
{
    $rows = nln_recap_fetch_all($stmt);
    return $rows[0] ?? [];
}

function nln_get_recap_summary_text(array $recap): string
{
    $metrics = $recap['metrics'] ?? [];
    $topGenre = $recap['top_genre'] ?? [];
    $topArtist = $recap['top_artists'][0] ?? [];
    $insights = $recap['insights'] ?? [];

    $totalViews = (int) ($metrics['total_views'] ?? 0);
    $uniqueSongs = (int) ($metrics['unique_songs'] ?? 0);
    $genreName = trim((string) ($topGenre['genre_name'] ?? ''));
    $artistName = trim((string) ($topArtist['artist_name'] ?? ''));
    $style = (string) ($insights['listening_style'] ?? 'balanced');
    $activeDays = (int) ($metrics['active_days'] ?? 0);

    if ($totalViews <= 0) {
        return 'Tháng này bạn chưa có đủ hoạt động để tạo recap âm nhạc.';
    }

    $parts = [];
    $parts[] = 'Bạn đã quan tâm ' . number_format($totalViews) . ' lượt bài hát trong tháng này.';

    if ($uniqueSongs > 0) {
        $parts[] = 'Bạn đã chạm tới ' . number_format($uniqueSongs) . ' bài hát khác nhau.';
    }

    if ($activeDays > 0) {
        $parts[] = 'Bạn hoạt động trong ' . number_format($activeDays) . ' ngày khác nhau.';
    }

    if ($genreName !== '') {
        $parts[] = 'Thể loại nổi bật nhất của bạn là ' . $genreName . '.';
    }

    if ($artistName !== '') {
        $parts[] = 'Nghệ sĩ xuất hiện nhiều nhất là ' . $artistName . '.';
    }

    if ($style === 'discovery') {
        $parts[] = 'Dữ liệu cho thấy bạn thiên về khám phá bài hát mới.';
    } elseif ($style === 'repeat') {
        $parts[] = 'Bạn có xu hướng quay lại những bài hát yêu thích nhiều lần.';
    } else {
        $parts[] = 'Gu nghe tháng này khá cân bằng giữa khám phá và nghe lại.';
    }

    return implode(' ', $parts);
}

function nln_get_user_monthly_recap(mysqli $conn, int $userId, int $month, int $year): array
{
    $period = nln_get_recap_period($month, $year);
    $month = (int) $period['month'];
    $year = (int) $period['year'];

    $recap = [
        'period' => $period,
        'metrics' => [
            'total_views' => 0,
            'unique_songs' => 0,
            'unique_artists' => 0,
            'unique_albums' => 0,
            'active_days' => 0,
        ],
        'top_songs' => [],
        'top_artists' => [],
        'top_album' => [],
        'top_genre' => [],
        'insights' => [
            'repeat_songs' => 0,
            'discovery_songs' => 0,
            'listening_style' => 'balanced',
            'peak_day' => '',
            'peak_day_total_views' => 0,
            'top_genre_share_percent' => 0,
        ],
        'summary_text' => '',
    ];

    $sqlMetrics = "
        SELECT
            COUNT(*) AS total_views,
            COUNT(DISTINCT l.song_id) AS unique_songs,
            COUNT(DISTINCT s.artist_id) AS unique_artists,
            COUNT(DISTINCT s.album_id) AS unique_albums,
            COUNT(DISTINCT DATE(l.search_time)) AS active_days
        FROM search_logs l
        INNER JOIN songs s ON s.song_id = l.song_id
        WHERE l.user_id = ?
          AND MONTH(l.search_time) = ?
          AND YEAR(l.search_time) = ?
    ";

    if ($stmt = $conn->prepare($sqlMetrics)) {
        $stmt->bind_param('iii', $userId, $month, $year);
        $row = nln_recap_fetch_one($stmt);
        $stmt->close();

        if ($row) {
            $recap['metrics'] = [
                'total_views' => (int) ($row['total_views'] ?? 0),
                'unique_songs' => (int) ($row['unique_songs'] ?? 0),
                'unique_artists' => (int) ($row['unique_artists'] ?? 0),
                'unique_albums' => (int) ($row['unique_albums'] ?? 0),
                'active_days' => (int) ($row['active_days'] ?? 0),
            ];
        }
    }

    if ((int) $recap['metrics']['total_views'] <= 0) {
        $recap['summary_text'] = nln_get_recap_summary_text($recap);
        return $recap;
    }

    $sqlTopSongs = "
        SELECT
            s.song_id,
            s.title,
            s.cover_image AS song_cover,
            ar.artist_id,
            ar.artist_name,
            al.album_id,
            al.album_name,
            al.cover_image AS album_cover,
            COUNT(*) AS total_views,
            MAX(l.search_time) AS last_seen_at
        FROM search_logs l
        INNER JOIN songs s ON s.song_id = l.song_id
        INNER JOIN artists ar ON ar.artist_id = s.artist_id
        LEFT JOIN albums al ON al.album_id = s.album_id
        WHERE l.user_id = ?
          AND MONTH(l.search_time) = ?
          AND YEAR(l.search_time) = ?
        GROUP BY
            s.song_id,
            s.title,
            s.cover_image,
            ar.artist_id,
            ar.artist_name,
            al.album_id,
            al.album_name,
            al.cover_image
        ORDER BY total_views DESC, last_seen_at DESC, s.title ASC
        LIMIT 5
    ";

    if ($stmt = $conn->prepare($sqlTopSongs)) {
        $stmt->bind_param('iii', $userId, $month, $year);
        $rows = nln_recap_fetch_all($stmt);
        $stmt->close();

        foreach ($rows as $row) {
            $row['total_views'] = (int) ($row['total_views'] ?? 0);
            $recap['top_songs'][] = $row;
        }
    }

    $sqlTopArtists = "
        SELECT
            ar.artist_id,
            ar.artist_name,
            ar.avatar,
            COUNT(*) AS total_views
        FROM search_logs l
        INNER JOIN songs s ON s.song_id = l.song_id
        INNER JOIN artists ar ON ar.artist_id = s.artist_id
        WHERE l.user_id = ?
          AND MONTH(l.search_time) = ?
          AND YEAR(l.search_time) = ?
        GROUP BY ar.artist_id, ar.artist_name, ar.avatar
        ORDER BY total_views DESC, ar.artist_name ASC
        LIMIT 3
    ";

    if ($stmt = $conn->prepare($sqlTopArtists)) {
        $stmt->bind_param('iii', $userId, $month, $year);
        $rows = nln_recap_fetch_all($stmt);
        $stmt->close();

        foreach ($rows as $row) {
            $row['total_views'] = (int) ($row['total_views'] ?? 0);
            $recap['top_artists'][] = $row;
        }
    }

    $sqlTopAlbum = "
        SELECT
            al.album_id,
            al.album_name,
            al.cover_image,
            ar.artist_id,
            ar.artist_name,
            COUNT(*) AS total_views
        FROM search_logs l
        INNER JOIN songs s ON s.song_id = l.song_id
        INNER JOIN albums al ON al.album_id = s.album_id
        INNER JOIN artists ar ON ar.artist_id = al.artist_id
        WHERE l.user_id = ?
          AND MONTH(l.search_time) = ?
          AND YEAR(l.search_time) = ?
          AND s.album_id IS NOT NULL
        GROUP BY
            al.album_id,
            al.album_name,
            al.cover_image,
            ar.artist_id,
            ar.artist_name
        ORDER BY total_views DESC, al.album_name ASC
        LIMIT 1
    ";

    if ($stmt = $conn->prepare($sqlTopAlbum)) {
        $stmt->bind_param('iii', $userId, $month, $year);
        $row = nln_recap_fetch_one($stmt);
        $stmt->close();

        if ($row) {
            $row['total_views'] = (int) ($row['total_views'] ?? 0);
            $recap['top_album'] = $row;
        }
    }

    $sqlTopGenre = "
        SELECT
            g.genre_id,
            g.genre_name,
            COUNT(*) AS total_views
        FROM search_logs l
        INNER JOIN songs s ON s.song_id = l.song_id
        INNER JOIN genres g ON g.genre_id = s.genre_id
        WHERE l.user_id = ?
          AND MONTH(l.search_time) = ?
          AND YEAR(l.search_time) = ?
          AND s.genre_id IS NOT NULL
        GROUP BY g.genre_id, g.genre_name
        ORDER BY total_views DESC, g.genre_name ASC
        LIMIT 1
    ";

    if ($stmt = $conn->prepare($sqlTopGenre)) {
        $stmt->bind_param('iii', $userId, $month, $year);
        $row = nln_recap_fetch_one($stmt);
        $stmt->close();

        if ($row) {
            $row['total_views'] = (int) ($row['total_views'] ?? 0);
            $recap['top_genre'] = $row;
        }
    }

    $sqlInsights = "
        SELECT
            SUM(CASE WHEN per_song.total_hits >= 2 THEN 1 ELSE 0 END) AS repeat_songs,
            SUM(CASE WHEN per_song.total_hits = 1 THEN 1 ELSE 0 END) AS discovery_songs
        FROM (
            SELECT l.song_id, COUNT(*) AS total_hits
            FROM search_logs l
            WHERE l.user_id = ?
              AND MONTH(l.search_time) = ?
              AND YEAR(l.search_time) = ?
            GROUP BY l.song_id
        ) AS per_song
    ";

    if ($stmt = $conn->prepare($sqlInsights)) {
        $stmt->bind_param('iii', $userId, $month, $year);
        $row = nln_recap_fetch_one($stmt);
        $stmt->close();

        $repeatSongs = (int) ($row['repeat_songs'] ?? 0);
        $discoverySongs = (int) ($row['discovery_songs'] ?? 0);

        $style = 'balanced';
        if ($repeatSongs > $discoverySongs) {
            $style = 'repeat';
        } elseif ($discoverySongs > $repeatSongs) {
            $style = 'discovery';
        }

        $recap['insights'] = [
            'repeat_songs' => $repeatSongs,
            'discovery_songs' => $discoverySongs,
            'listening_style' => $style,
            'peak_day' => '',
            'peak_day_total_views' => 0,
            'top_genre_share_percent' => 0,
        ];
    }

    $sqlPeakDay = "
        SELECT
            DATE(l.search_time) AS peak_day,
            COUNT(*) AS total_views
        FROM search_logs l
        WHERE l.user_id = ?
          AND MONTH(l.search_time) = ?
          AND YEAR(l.search_time) = ?
        GROUP BY DATE(l.search_time)
        ORDER BY total_views DESC, peak_day DESC
        LIMIT 1
    ";

    if ($stmt = $conn->prepare($sqlPeakDay)) {
        $stmt->bind_param('iii', $userId, $month, $year);
        $row = nln_recap_fetch_one($stmt);
        $stmt->close();

        if ($row) {
            $recap['insights']['peak_day'] = (string) ($row['peak_day'] ?? '');
            $recap['insights']['peak_day_total_views'] = (int) ($row['total_views'] ?? 0);
        }
    }

    $topGenreViews = (int) ($recap['top_genre']['total_views'] ?? 0);
    $totalViews = (int) ($recap['metrics']['total_views'] ?? 0);
    if ($topGenreViews > 0 && $totalViews > 0) {
        $recap['insights']['top_genre_share_percent'] = (int) round(($topGenreViews / $totalViews) * 100);
    }

    $recap['summary_text'] = nln_get_recap_summary_text($recap);

    return $recap;
}
