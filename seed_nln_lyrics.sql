USE nln_lyrics;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO users (user_id, username, email, password_hash, full_name, role, avatar, created_at, updated_at) VALUES
(1, 'admin', 'admin@nln.local', 'admin123', 'System Admin', 'admin', NULL, NOW(), NOW()),
(2, 'demo', 'demo@nln.local', 'demo123', 'Demo User', 'user', NULL, NOW(), NOW());

INSERT INTO genres (genre_id, genre_name) VALUES
(1, 'Pop'),
(2, 'Ballad'),
(3, 'R&B'),
(4, 'Indie'),
(5, 'Alternative');

INSERT INTO artists (artist_id, artist_name, avatar, bio, country, birth_year, created_at) VALUES
(1, 'Taylor Swift', NULL, 'American singer-songwriter known for storytelling lyrics and multi-era musical reinvention.', 'USA', 1989, NOW()),
(2, 'The Weeknd', NULL, 'Canadian artist blending pop, R&B and cinematic production.', 'Canada', 1990, NOW()),
(3, 'Sơn Tùng M-TP', NULL, 'Vietnamese pop artist with strong melodic identity and modern mainstream appeal.', 'Vietnam', 1994, NOW());

INSERT INTO albums (album_id, album_name, artist_id, cover_image, release_year, release_date, created_at) VALUES
(1, 'Midnights', 1, NULL, 2022, '2022-10-21', NOW()),
(2, '1989', 1, NULL, 2014, '2014-10-27', NOW()),
(3, 'After Hours', 2, NULL, 2020, '2020-03-20', NOW()),
(4, 'Dawn FM', 2, NULL, 2022, '2022-01-07', NOW()),
(5, 'Chung Ta', 3, NULL, 2020, '2020-12-20', NOW());

INSERT INTO songs (song_id, title, artist_id, genre_id, album_id, cover_image, release_date, language, lyrics, meaning, youtube_video_id, created_at) VALUES
(1, 'Anti-Hero', 1, 1, 1, NULL, '2022-10-21', 'English', 'Seed lyrics placeholder for Anti-Hero.', 'A self-aware song about insecurity and the fear of becoming the problem in your own story.', 'b1kbLwvqugk', NOW()),
(2, 'Lavender Haze', 1, 1, 1, NULL, '2022-10-21', 'English', 'Seed lyrics placeholder for Lavender Haze.', 'A song about protecting love from social pressure and outside noise.', 'h8DLofLM7No', NOW()),
(3, 'Blank Space', 1, 1, 2, NULL, '2014-10-27', 'English', 'Seed lyrics placeholder for Blank Space.', 'A satirical take on public image, romance and media narratives.', 'e-ORhEE9VVg', NOW()),
(4, 'Style', 1, 1, 2, NULL, '2014-10-27', 'English', 'Seed lyrics placeholder for Style.', 'A sleek portrait of attraction, memory and a relationship with lasting chemistry.', '-CmadmM5cOk', NOW()),
(5, 'Blinding Lights', 2, 3, 3, NULL, '2019-11-29', 'English', 'Seed lyrics placeholder for Blinding Lights.', 'A rush of longing and emotional dependency wrapped in retro-pop energy.', '4NRXx6U8ABQ', NOW()),
(6, 'Save Your Tears', 2, 3, 3, NULL, '2020-03-20', 'English', 'Seed lyrics placeholder for Save Your Tears.', 'A reflective breakup track centered on regret, distance and emotional timing.', 'XXYlFuWEuKI', NOW()),
(7, 'Take My Breath', 2, 3, 4, NULL, '2021-08-06', 'English', 'Seed lyrics placeholder for Take My Breath.', 'A dramatic song about desire, tension and surrendering to intensity.', 'rhTl_OyehF8', NOW()),
(8, 'Chung Ta Cua Hien Tai', 3, 2, 5, NULL, '2020-12-20', 'Vietnamese', 'Seed lyrics placeholder for Chung Ta Cua Hien Tai.', 'A bittersweet reflection on love, separation and emotional memory.', 'psZ1g9fMfeo', NOW()),
(9, 'Muon Roi Ma Sao Con', 3, 1, 5, NULL, '2021-04-29', 'Vietnamese', 'Seed lyrics placeholder for Muon Roi Ma Sao Con.', 'A modern pop song about hesitation, timing and unfinished emotions.', 'xypzmu5mMPY', NOW());

INSERT INTO news (news_id, title, image, summary, content, artist_id, created_at) VALUES
(1, 'NLN Lyrics khoi dong lai he thong du lieu', NULL, 'Database duoc tao moi de tiep tuc phat trien va demo.', 'He thong da duoc khoi tao lai voi schema moi va bo du lieu seed phuc vu qua trinh demo.', NULL, NOW()),
(2, 'Taylor Swift tiep tuc la nghe si duoc tim kiem nhieu', NULL, 'Luu luong tim kiem va tuong tac van rat cao tren he thong.', 'Du lieu seed uu tien cac nghe si co do nhan dien cao de de dang trinh dien giao dien va chuc nang.', 1, NOW()),
(3, 'The Weeknd va Son Tung M-TP duoc bo sung vao bo du lieu mau', NULL, 'Bo du lieu moi tap trung vao kha nang minh hoa public pages.', 'Cac nghe si duoc chon nham phuc vu test profile, album, post va recommendation.', NULL, NOW());

INSERT INTO notifications (notification_id, user_id, news_id, artist_id, is_read, created_at) VALUES
(1, 2, 1, NULL, 0, NOW()),
(2, 2, 2, 1, 0, NOW()),
(3, 2, 3, NULL, 1, NOW());

INSERT INTO search_logs (log_id, user_id, song_id, song_title, artist_name, cover_image, search_time) VALUES
(1, 2, 1, 'Anti-Hero', 'Taylor Swift', NULL, NOW() - INTERVAL 7 DAY),
(2, 2, 3, 'Blank Space', 'Taylor Swift', NULL, NOW() - INTERVAL 5 DAY),
(3, 2, 5, 'Blinding Lights', 'The Weeknd', NULL, NOW() - INTERVAL 4 DAY),
(4, 2, 8, 'Chung Ta Cua Hien Tai', 'Sơn Tùng M-TP', NULL, NOW() - INTERVAL 2 DAY),
(5, 2, 6, 'Save Your Tears', 'The Weeknd', NULL, NOW() - INTERVAL 1 DAY);

INSERT INTO artist_follows (id, user_id, artist_id, created_at) VALUES
(1, 2, 1, NOW()),
(2, 2, 3, NOW());

INSERT INTO album_favorites (id, user_id, album_id, created_at) VALUES
(1, 2, 1, NOW()),
(2, 2, 3, NOW());

INSERT INTO user_recommendations (id, user_id, song_id, base_score, updated_at) VALUES
(1, 2, 2, 92.50, NOW()),
(2, 2, 6, 90.00, NOW()),
(3, 2, 9, 88.00, NOW());

INSERT INTO comments (comment_id, song_id, user_id, parent_comment_id, content, created_at, updated_at) VALUES
(1, 1, 2, NULL, 'Bai nay rat hop de demo phan meaning va related songs.', NOW(), NOW()),
(2, 1, 1, 1, 'Da ghi nhan, se tiep tuc toi uu giao dien va du lieu.', NOW(), NOW()),
(3, 5, 2, NULL, 'Section lyrics va video hoat dong on tren du lieu moi.', NOW(), NOW());

INSERT INTO comment_likes (like_id, comment_id, user_id, created_at) VALUES
(1, 1, 1, NOW()),
(2, 2, 2, NOW());

INSERT INTO album_ratings (rating_id, album_id, user_id, rating, created_at, updated_at) VALUES
(1, 1, 2, 5, NOW(), NOW()),
(2, 3, 2, 4, NOW(), NOW());

INSERT INTO playlists (playlist_id, user_id, playlist_name, description, created_at, updated_at) VALUES
(1, 2, 'Night Drive Picks', 'Playlist mau de test profile va them bai hat tu post.php.', NOW(), NOW()),
(2, 2, 'Vietnamese Favorites', 'Tap hop bai hat V-Pop cho demo.', NOW(), NOW());

INSERT INTO playlist_songs (playlist_song_id, playlist_id, song_id, added_at) VALUES
(1, 1, 1, NOW()),
(2, 1, 5, NOW()),
(3, 1, 6, NOW()),
(4, 2, 8, NOW()),
(5, 2, 9, NOW());

INSERT INTO api_logs (log_id, endpoint, method, payload, created_at, user_id) VALUES
(1, '/public/includes/ajax_fetch_lyrics.php', 'GET', '{"song_id":1}', NOW(), 2),
(2, '/public/includes/ajax_analyze_lyrics.php', 'GET', '{"song_id":1,"force":0}', NOW(), 2);

SET FOREIGN_KEY_CHECKS = 1;
