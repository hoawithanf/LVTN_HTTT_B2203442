<?php

require_once __DIR__ . '/song_helpers.php';

function nln_related_song_query(mysqli $conn, int $currentSongId, string $matchType, int $matchId, int $limit = 12): array
{
    if ($matchId <= 0) {
        return [];
    }

    $allowedFields = [
        'artist' => 's.artist_id',
        'album' => 's.album_id',
        'genre' => 's.genre_id',
    ];

    if (!isset($allowedFields[$matchType])) {
        return [];
    }

    $field = $allowedFields[$matchType];

    $sql = "
        SELECT
            s.song_id,
            s.title,
            s.artist_id,
            s.album_id,
            s.genre_id,
            s.release_date,
            s.cover_image AS song_cover,
            a.artist_name,
            al.album_name,
            al.cover_image AS album_cover,
            COALESCE(COUNT(sl.log_id), 0) AS popularity
        FROM songs s
        INNER JOIN artists a ON a.artist_id = s.artist_id
        LEFT JOIN albums al ON al.album_id = s.album_id
        LEFT JOIN search_logs sl ON sl.song_id = s.song_id
        WHERE s.song_id <> ?
          AND {$field} = ?
        GROUP BY
            s.song_id,
            s.title,
            s.artist_id,
            s.album_id,
            s.genre_id,
            s.release_date,
            s.cover_image,
            a.artist_name,
            al.album_name,
            al.cover_image
        ORDER BY popularity DESC, s.release_date DESC, s.song_id DESC
        LIMIT ?
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('iii', $currentSongId, $matchId, $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $labelMap = [
        'artist' => 'Cùng nghệ sĩ',
        'album' => 'Cùng album',
        'genre' => 'Cùng thể loại',
    ];

    foreach ($rows as &$row) {
        $row['cover'] = nln_public_song_cover_path($row['song_cover'] ?? null, $row['album_cover'] ?? null);
        $row['popularity'] = (int) ($row['popularity'] ?? 0);
        $row['relation_label'] = $labelMap[$matchType];
        $row['_source'] = $matchType;
    }
    unset($row);

    return $rows;
}

function nln_fetch_related_songs(
    mysqli $conn,
    int $currentSongId,
    int $artistId,
    ?int $albumId,
    ?int $genreId,
    int $limit = 6
): array {
    $artistRows = nln_related_song_query($conn, $currentSongId, 'artist', $artistId, 12);
    $albumRows = $albumId !== null && $albumId > 0 ? nln_related_song_query($conn, $currentSongId, 'album', $albumId, 12) : [];
    $genreRows = $genreId !== null && $genreId > 0 ? nln_related_song_query($conn, $currentSongId, 'genre', $genreId, 12) : [];

    $result = [];
    $seenSongIds = [];
    $seenAlbumIds = [];

    $takeRows = static function (array $rows, int $targetCount, bool $avoidDuplicateAlbum) use (&$result, &$seenSongIds, &$seenAlbumIds, $limit): void {
        foreach ($rows as $row) {
            $songId = (int) ($row['song_id'] ?? 0);
            $rowAlbumId = (int) ($row['album_id'] ?? 0);
            if ($songId <= 0 || isset($seenSongIds[$songId])) {
                continue;
            }

            if ($avoidDuplicateAlbum && $rowAlbumId > 0 && isset($seenAlbumIds[$rowAlbumId])) {
                continue;
            }

            $seenSongIds[$songId] = true;
            if ($rowAlbumId > 0) {
                $seenAlbumIds[$rowAlbumId] = true;
            }

            $result[] = $row;
            if (count($result) >= $limit || count($result) >= $targetCount) {
                if (count($result) >= $limit) {
                    return;
                }
                if (count($result) >= $targetCount) {
                    return;
                }
            }
        }
    };

    $artistQuota = min(3, $limit);
    $albumQuota = min(2, max(0, $limit - $artistQuota));
    $genreQuota = min(2, max(0, $limit - $artistQuota - $albumQuota));

    $takeRows($artistRows, $artistQuota, true);
    $takeRows($albumRows, $artistQuota + $albumQuota, false);
    $takeRows($genreRows, $artistQuota + $albumQuota + $genreQuota, true);

    if (count($result) < $limit) {
        foreach ([$artistRows, $albumRows, $genreRows] as $pool) {
            foreach ($pool as $row) {
                $songId = (int) ($row['song_id'] ?? 0);
                if ($songId <= 0 || isset($seenSongIds[$songId])) {
                    continue;
                }

                $seenSongIds[$songId] = true;
                $result[] = $row;
                if (count($result) >= $limit) {
                    break 2;
                }
            }
        }
    }

    return array_slice($result, 0, $limit);
}
