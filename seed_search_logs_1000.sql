USE nln_lyrics;

SET NAMES utf8mb4;

DELETE FROM search_logs;

DROP TEMPORARY TABLE IF EXISTS tmp_numbers;
CREATE TEMPORARY TABLE tmp_numbers (
    n INT NOT NULL PRIMARY KEY
);

DROP TEMPORARY TABLE IF EXISTS tmp_user_pool;
CREATE TEMPORARY TABLE tmp_user_pool (
    rn INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL
) ENGINE=MEMORY;

DROP TEMPORARY TABLE IF EXISTS tmp_song_pool;
CREATE TEMPORARY TABLE tmp_song_pool (
    rn INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    song_id INT NOT NULL,
    song_title VARCHAR(200) NOT NULL,
    artist_name VARCHAR(150) NOT NULL,
    cover_image VARCHAR(255) NULL
) ENGINE=MEMORY;

INSERT INTO tmp_numbers (n)
SELECT ones.n + tens.n * 10 + hundreds.n * 100 + 1
FROM
    (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) ones
CROSS JOIN
    (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) tens
CROSS JOIN
    (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) hundreds
WHERE ones.n + tens.n * 10 + hundreds.n * 100 < 1000;

INSERT INTO tmp_user_pool (user_id)
SELECT user_id
FROM users
WHERE role = 'user'
ORDER BY user_id;

INSERT INTO tmp_song_pool (song_id, song_title, artist_name, cover_image)
SELECT
    s.song_id,
    s.title,
    a.artist_name,
    COALESCE(NULLIF(s.cover_image, ''), NULLIF(al.cover_image, ''), '') AS cover_image
FROM songs s
JOIN artists a ON a.artist_id = s.artist_id
LEFT JOIN albums al ON al.album_id = s.album_id
ORDER BY s.song_id;

SET @user_count = (SELECT COUNT(*) FROM tmp_user_pool);
SET @song_count = (SELECT COUNT(*) FROM tmp_song_pool);

INSERT INTO search_logs (user_id, song_id, song_title, artist_name, cover_image, search_time)
SELECT
    picked_user.user_id,
    picked_song.song_id,
    picked_song.song_title,
    picked_song.artist_name,
    picked_song.cover_image,
    TIMESTAMP(
        DATE_SUB(CURDATE(), INTERVAL FLOOR(RAND(t.n * 97) * 120) DAY),
        SEC_TO_TIME(FLOOR(RAND(t.n * 131) * 86400))
    ) AS search_time
FROM tmp_numbers t
JOIN tmp_user_pool picked_user
    ON picked_user.rn = 1 + FLOOR(RAND(t.n * 17) * @user_count)
JOIN tmp_song_pool picked_song
    ON picked_song.rn = 1 + FLOOR(RAND(t.n * 29) * @song_count)
ORDER BY t.n;

DROP TEMPORARY TABLE IF EXISTS tmp_user_pool;
DROP TEMPORARY TABLE IF EXISTS tmp_song_pool;
DROP TEMPORARY TABLE IF EXISTS tmp_numbers;
