USE nln_lyrics;

SET NAMES utf8mb4;

-- Artists
UPDATE artists SET avatar = 'taylor_swift.jpg' WHERE artist_id = 1;
UPDATE artists SET avatar = 'sontung_mtp.webp' WHERE artist_id = 3;

-- Albums
UPDATE albums SET cover_image = 'midnights.jpg' WHERE album_id = 1;
UPDATE albums SET cover_image = '1989.jpg' WHERE album_id = 2;

-- Songs: reuse album art where a matching album cover exists
UPDATE songs SET cover_image = 'midnights.jpg' WHERE song_id IN (1, 2);
UPDATE songs SET cover_image = '1989.jpg' WHERE song_id IN (3, 4);

-- News
UPDATE news SET image = 'billboard.jpg' WHERE news_id = 1;
UPDATE news SET image = 'taylor_album.jpg' WHERE news_id = 2;
UPDATE news SET image = '1766509552_sontung_mtp.webp' WHERE news_id = 3;
