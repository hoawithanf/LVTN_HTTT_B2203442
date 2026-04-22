USE nln_lyrics;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE comment_likes;
TRUNCATE TABLE comments;
TRUNCATE TABLE search_logs;
TRUNCATE TABLE user_recommendations;
TRUNCATE TABLE playlist_songs;
TRUNCATE TABLE album_ratings;
TRUNCATE TABLE album_favorites;
TRUNCATE TABLE artist_follows;
TRUNCATE TABLE notifications;
TRUNCATE TABLE news;
TRUNCATE TABLE api_logs;
TRUNCATE TABLE songs;
TRUNCATE TABLE albums;
TRUNCATE TABLE artists;
TRUNCATE TABLE genres;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO genres (genre_id, genre_name) VALUES
(1, 'Country'),
(2, 'Pop'),
(3, 'Alternative Pop'),
(4, 'Indie Folk');

INSERT INTO artists (artist_id, artist_name, avatar, bio, country, birth_year, created_at) VALUES
(1, 'Taylor Swift', 'taylor_swift.jpg', 'American singer-songwriter known for narrative songwriting and era-based reinvention.', 'USA', 1989, NOW());

INSERT INTO albums (album_id, album_name, artist_id, cover_image, release_year, release_date, created_at) VALUES
(1, 'Taylor Swift', 1, 'taylor_swift.jpg', 2006, '2006-10-24', NOW()),
(2, 'Fearless', 1, 'fearless.jpg', 2008, '2008-11-11', NOW()),
(3, 'Speak Now', 1, 'speak_now.jpg', 2010, '2010-10-25', NOW()),
(4, 'Red', 1, 'red.jpg', 2012, '2012-10-22', NOW()),
(5, '1989', 1, '1989.jpg', 2014, '2014-10-27', NOW()),
(6, 'Reputation', 1, 'reputation.jpg', 2017, '2017-11-10', NOW()),
(7, 'Lover', 1, 'lover.jpg', 2019, '2019-08-23', NOW()),
(8, 'Folklore', 1, 'folklore.jpg', 2020, '2020-07-24', NOW()),
(9, 'Evermore', 1, 'evermore.jpg', 2020, '2020-12-11', NOW()),
(10, 'Midnights', 1, 'midnights.jpg', 2022, '2022-10-21', NOW()),
(11, 'The Tortured Poets Department', 1, 'tortured_poets.jpg', 2024, '2024-04-19', NOW()),
(12, 'The Life of a Showgirl', 1, 'showgirl.jpg', 2025, '2025-10-03', NOW());

INSERT INTO songs (title, artist_id, genre_id, album_id, cover_image, release_date, language, lyrics, meaning, youtube_video_id, created_at) VALUES
('Tim McGraw', 1, 1, 1, 'taylor_swift.jpg', '2006-10-24', 'English', NULL, NULL, NULL, NOW()),
('Picture to Burn', 1, 1, 1, 'taylor_swift.jpg', '2006-10-24', 'English', NULL, NULL, NULL, NOW()),
('Teardrops on My Guitar', 1, 1, 1, 'taylor_swift.jpg', '2006-10-24', 'English', NULL, NULL, NULL, NOW()),
('A Place in This World', 1, 1, 1, 'taylor_swift.jpg', '2006-10-24', 'English', NULL, NULL, NULL, NOW()),
('Cold as You', 1, 1, 1, 'taylor_swift.jpg', '2006-10-24', 'English', NULL, NULL, NULL, NOW()),
('The Outside', 1, 1, 1, 'taylor_swift.jpg', '2006-10-24', 'English', NULL, NULL, NULL, NOW()),
('Tied Together with a Smile', 1, 1, 1, 'taylor_swift.jpg', '2006-10-24', 'English', NULL, NULL, NULL, NOW()),
('Stay Beautiful', 1, 1, 1, 'taylor_swift.jpg', '2006-10-24', 'English', NULL, NULL, NULL, NOW()),
('Should''ve Said No', 1, 1, 1, 'taylor_swift.jpg', '2006-10-24', 'English', NULL, NULL, NULL, NOW()),
('Mary''s Song (Oh My My My)', 1, 1, 1, 'taylor_swift.jpg', '2006-10-24', 'English', NULL, NULL, NULL, NOW()),
('Our Song', 1, 1, 1, 'taylor_swift.jpg', '2006-10-24', 'English', NULL, NULL, NULL, NOW()),

('Fearless', 1, 1, 2, 'fearless.jpg', '2008-11-11', 'English', NULL, NULL, NULL, NOW()),
('Fifteen', 1, 1, 2, 'fearless.jpg', '2008-11-11', 'English', NULL, NULL, NULL, NOW()),
('Love Story', 1, 1, 2, 'fearless.jpg', '2008-11-11', 'English', NULL, NULL, NULL, NOW()),
('Hey Stephen', 1, 1, 2, 'fearless.jpg', '2008-11-11', 'English', NULL, NULL, NULL, NOW()),
('White Horse', 1, 1, 2, 'fearless.jpg', '2008-11-11', 'English', NULL, NULL, NULL, NOW()),
('You Belong with Me', 1, 1, 2, 'fearless.jpg', '2008-11-11', 'English', NULL, NULL, NULL, NOW()),
('Breathe', 1, 1, 2, 'fearless.jpg', '2008-11-11', 'English', NULL, NULL, NULL, NOW()),
('Tell Me Why', 1, 1, 2, 'fearless.jpg', '2008-11-11', 'English', NULL, NULL, NULL, NOW()),
('You''re Not Sorry', 1, 1, 2, 'fearless.jpg', '2008-11-11', 'English', NULL, NULL, NULL, NOW()),
('The Way I Loved You', 1, 1, 2, 'fearless.jpg', '2008-11-11', 'English', NULL, NULL, NULL, NOW()),
('Forever & Always', 1, 1, 2, 'fearless.jpg', '2008-11-11', 'English', NULL, NULL, NULL, NOW()),
('The Best Day', 1, 1, 2, 'fearless.jpg', '2008-11-11', 'English', NULL, NULL, NULL, NOW()),
('Change', 1, 1, 2, 'fearless.jpg', '2008-11-11', 'English', NULL, NULL, NULL, NOW()),

('Mine', 1, 1, 3, 'speak_now.jpg', '2010-10-25', 'English', NULL, NULL, NULL, NOW()),
('Sparks Fly', 1, 1, 3, 'speak_now.jpg', '2010-10-25', 'English', NULL, NULL, NULL, NOW()),
('Back to December', 1, 1, 3, 'speak_now.jpg', '2010-10-25', 'English', NULL, NULL, NULL, NOW()),
('Speak Now', 1, 1, 3, 'speak_now.jpg', '2010-10-25', 'English', NULL, NULL, NULL, NOW()),
('Dear John', 1, 1, 3, 'speak_now.jpg', '2010-10-25', 'English', NULL, NULL, NULL, NOW()),
('Mean', 1, 1, 3, 'speak_now.jpg', '2010-10-25', 'English', NULL, NULL, NULL, NOW()),
('The Story of Us', 1, 1, 3, 'speak_now.jpg', '2010-10-25', 'English', NULL, NULL, NULL, NOW()),
('Never Grow Up', 1, 1, 3, 'speak_now.jpg', '2010-10-25', 'English', NULL, NULL, NULL, NOW()),
('Enchanted', 1, 1, 3, 'speak_now.jpg', '2010-10-25', 'English', NULL, NULL, NULL, NOW()),
('Better than Revenge', 1, 1, 3, 'speak_now.jpg', '2010-10-25', 'English', NULL, NULL, NULL, NOW()),
('Innocent', 1, 1, 3, 'speak_now.jpg', '2010-10-25', 'English', NULL, NULL, NULL, NOW()),
('Haunted', 1, 1, 3, 'speak_now.jpg', '2010-10-25', 'English', NULL, NULL, NULL, NOW()),
('Last Kiss', 1, 1, 3, 'speak_now.jpg', '2010-10-25', 'English', NULL, NULL, NULL, NOW()),
('Long Live', 1, 1, 3, 'speak_now.jpg', '2010-10-25', 'English', NULL, NULL, NULL, NOW()),

('State of Grace', 1, 2, 4, 'red.jpg', '2012-10-22', 'English', NULL, NULL, NULL, NOW()),
('Red', 1, 2, 4, 'red.jpg', '2012-10-22', 'English', NULL, NULL, NULL, NOW()),
('Treacherous', 1, 2, 4, 'red.jpg', '2012-10-22', 'English', NULL, NULL, NULL, NOW()),
('I Knew You Were Trouble', 1, 2, 4, 'red.jpg', '2012-10-22', 'English', NULL, NULL, NULL, NOW()),
('All Too Well', 1, 2, 4, 'red.jpg', '2012-10-22', 'English', NULL, NULL, NULL, NOW()),
('22', 1, 2, 4, 'red.jpg', '2012-10-22', 'English', NULL, NULL, NULL, NOW()),
('I Almost Do', 1, 2, 4, 'red.jpg', '2012-10-22', 'English', NULL, NULL, NULL, NOW()),
('We Are Never Ever Getting Back Together', 1, 2, 4, 'red.jpg', '2012-10-22', 'English', NULL, NULL, NULL, NOW()),
('Stay Stay Stay', 1, 2, 4, 'red.jpg', '2012-10-22', 'English', NULL, NULL, NULL, NOW()),
('The Last Time', 1, 2, 4, 'red.jpg', '2012-10-22', 'English', NULL, NULL, NULL, NOW()),
('Holy Ground', 1, 2, 4, 'red.jpg', '2012-10-22', 'English', NULL, NULL, NULL, NOW()),
('Sad Beautiful Tragic', 1, 2, 4, 'red.jpg', '2012-10-22', 'English', NULL, NULL, NULL, NOW()),
('The Lucky One', 1, 2, 4, 'red.jpg', '2012-10-22', 'English', NULL, NULL, NULL, NOW()),
('Everything Has Changed', 1, 2, 4, 'red.jpg', '2012-10-22', 'English', NULL, NULL, NULL, NOW()),
('Starlight', 1, 2, 4, 'red.jpg', '2012-10-22', 'English', NULL, NULL, NULL, NOW()),
('Begin Again', 1, 2, 4, 'red.jpg', '2012-10-22', 'English', NULL, NULL, NULL, NOW()),

('Welcome to New York', 1, 2, 5, '1989.jpg', '2014-10-27', 'English', NULL, NULL, NULL, NOW()),
('Blank Space', 1, 2, 5, '1989.jpg', '2014-10-27', 'English', NULL, NULL, NULL, NOW()),
('Style', 1, 2, 5, '1989.jpg', '2014-10-27', 'English', NULL, NULL, NULL, NOW()),
('Out of the Woods', 1, 2, 5, '1989.jpg', '2014-10-27', 'English', NULL, NULL, NULL, NOW()),
('All You Had to Do Was Stay', 1, 2, 5, '1989.jpg', '2014-10-27', 'English', NULL, NULL, NULL, NOW()),
('Shake It Off', 1, 2, 5, '1989.jpg', '2014-10-27', 'English', NULL, NULL, NULL, NOW()),
('I Wish You Would', 1, 2, 5, '1989.jpg', '2014-10-27', 'English', NULL, NULL, NULL, NOW()),
('Bad Blood', 1, 2, 5, '1989.jpg', '2014-10-27', 'English', NULL, NULL, NULL, NOW()),
('Wildest Dreams', 1, 2, 5, '1989.jpg', '2014-10-27', 'English', NULL, NULL, NULL, NOW()),
('How You Get the Girl', 1, 2, 5, '1989.jpg', '2014-10-27', 'English', NULL, NULL, NULL, NOW()),
('This Love', 1, 2, 5, '1989.jpg', '2014-10-27', 'English', NULL, NULL, NULL, NOW()),
('I Know Places', 1, 2, 5, '1989.jpg', '2014-10-27', 'English', NULL, NULL, NULL, NOW()),
('Clean', 1, 2, 5, '1989.jpg', '2014-10-27', 'English', NULL, NULL, NULL, NOW()),

('...Ready for It?', 1, 3, 6, 'reputation.jpg', '2017-11-10', 'English', NULL, NULL, NULL, NOW()),
('End Game', 1, 3, 6, 'reputation.jpg', '2017-11-10', 'English', NULL, NULL, NULL, NOW()),
('I Did Something Bad', 1, 3, 6, 'reputation.jpg', '2017-11-10', 'English', NULL, NULL, NULL, NOW()),
('Don''t Blame Me', 1, 3, 6, 'reputation.jpg', '2017-11-10', 'English', NULL, NULL, NULL, NOW()),
('Delicate', 1, 3, 6, 'reputation.jpg', '2017-11-10', 'English', NULL, NULL, NULL, NOW()),
('Look What You Made Me Do', 1, 3, 6, 'reputation.jpg', '2017-11-10', 'English', NULL, NULL, NULL, NOW()),
('So It Goes...', 1, 3, 6, 'reputation.jpg', '2017-11-10', 'English', NULL, NULL, NULL, NOW()),
('Gorgeous', 1, 3, 6, 'reputation.jpg', '2017-11-10', 'English', NULL, NULL, NULL, NOW()),
('Getaway Car', 1, 3, 6, 'reputation.jpg', '2017-11-10', 'English', NULL, NULL, NULL, NOW()),
('King of My Heart', 1, 3, 6, 'reputation.jpg', '2017-11-10', 'English', NULL, NULL, NULL, NOW()),
('Dancing with Our Hands Tied', 1, 3, 6, 'reputation.jpg', '2017-11-10', 'English', NULL, NULL, NULL, NOW()),
('Dress', 1, 3, 6, 'reputation.jpg', '2017-11-10', 'English', NULL, NULL, NULL, NOW()),
('This Is Why We Can''t Have Nice Things', 1, 3, 6, 'reputation.jpg', '2017-11-10', 'English', NULL, NULL, NULL, NOW()),
('Call It What You Want', 1, 3, 6, 'reputation.jpg', '2017-11-10', 'English', NULL, NULL, NULL, NOW()),
('New Year''s Day', 1, 3, 6, 'reputation.jpg', '2017-11-10', 'English', NULL, NULL, NULL, NOW()),

('I Forgot That You Existed', 1, 2, 7, 'lover.jpg', '2019-08-23', 'English', NULL, NULL, NULL, NOW()),
('Cruel Summer', 1, 2, 7, 'lover.jpg', '2019-08-23', 'English', NULL, NULL, NULL, NOW()),
('Lover', 1, 2, 7, 'lover.jpg', '2019-08-23', 'English', NULL, NULL, NULL, NOW()),
('The Man', 1, 2, 7, 'lover.jpg', '2019-08-23', 'English', NULL, NULL, NULL, NOW()),
('The Archer', 1, 2, 7, 'lover.jpg', '2019-08-23', 'English', NULL, NULL, NULL, NOW()),
('I Think He Knows', 1, 2, 7, 'lover.jpg', '2019-08-23', 'English', NULL, NULL, NULL, NOW()),
('Miss Americana & the Heartbreak Prince', 1, 2, 7, 'lover.jpg', '2019-08-23', 'English', NULL, NULL, NULL, NOW()),
('Paper Rings', 1, 2, 7, 'lover.jpg', '2019-08-23', 'English', NULL, NULL, NULL, NOW()),
('Cornelia Street', 1, 2, 7, 'lover.jpg', '2019-08-23', 'English', NULL, NULL, NULL, NOW()),
('Death by a Thousand Cuts', 1, 2, 7, 'lover.jpg', '2019-08-23', 'English', NULL, NULL, NULL, NOW()),
('London Boy', 1, 2, 7, 'lover.jpg', '2019-08-23', 'English', NULL, NULL, NULL, NOW()),
('Soon You''ll Get Better', 1, 2, 7, 'lover.jpg', '2019-08-23', 'English', NULL, NULL, NULL, NOW()),
('False God', 1, 2, 7, 'lover.jpg', '2019-08-23', 'English', NULL, NULL, NULL, NOW()),
('You Need to Calm Down', 1, 2, 7, 'lover.jpg', '2019-08-23', 'English', NULL, NULL, NULL, NOW()),
('Afterglow', 1, 2, 7, 'lover.jpg', '2019-08-23', 'English', NULL, NULL, NULL, NOW()),
('ME!', 1, 2, 7, 'lover.jpg', '2019-08-23', 'English', NULL, NULL, NULL, NOW()),
('It''s Nice to Have a Friend', 1, 2, 7, 'lover.jpg', '2019-08-23', 'English', NULL, NULL, NULL, NOW()),
('Daylight', 1, 2, 7, 'lover.jpg', '2019-08-23', 'English', NULL, NULL, NULL, NOW()),

('the 1', 1, 4, 8, 'folklore.jpg', '2020-07-24', 'English', NULL, NULL, NULL, NOW()),
('cardigan', 1, 4, 8, 'folklore.jpg', '2020-07-24', 'English', NULL, NULL, NULL, NOW()),
('the last great american dynasty', 1, 4, 8, 'folklore.jpg', '2020-07-24', 'English', NULL, NULL, NULL, NOW()),
('exile', 1, 4, 8, 'folklore.jpg', '2020-07-24', 'English', NULL, NULL, NULL, NOW()),
('my tears ricochet', 1, 4, 8, 'folklore.jpg', '2020-07-24', 'English', NULL, NULL, NULL, NOW()),
('mirrorball', 1, 4, 8, 'folklore.jpg', '2020-07-24', 'English', NULL, NULL, NULL, NOW()),
('seven', 1, 4, 8, 'folklore.jpg', '2020-07-24', 'English', NULL, NULL, NULL, NOW()),
('august', 1, 4, 8, 'folklore.jpg', '2020-07-24', 'English', NULL, NULL, NULL, NOW()),
('this is me trying', 1, 4, 8, 'folklore.jpg', '2020-07-24', 'English', NULL, NULL, NULL, NOW()),
('illicit affairs', 1, 4, 8, 'folklore.jpg', '2020-07-24', 'English', NULL, NULL, NULL, NOW()),
('invisible string', 1, 4, 8, 'folklore.jpg', '2020-07-24', 'English', NULL, NULL, NULL, NOW()),
('mad woman', 1, 4, 8, 'folklore.jpg', '2020-07-24', 'English', NULL, NULL, NULL, NOW()),
('epiphany', 1, 4, 8, 'folklore.jpg', '2020-07-24', 'English', NULL, NULL, NULL, NOW()),
('betty', 1, 4, 8, 'folklore.jpg', '2020-07-24', 'English', NULL, NULL, NULL, NOW()),
('peace', 1, 4, 8, 'folklore.jpg', '2020-07-24', 'English', NULL, NULL, NULL, NOW()),
('hoax', 1, 4, 8, 'folklore.jpg', '2020-07-24', 'English', NULL, NULL, NULL, NOW()),

('willow', 1, 4, 9, 'evermore.jpg', '2020-12-11', 'English', NULL, NULL, NULL, NOW()),
('champagne problems', 1, 4, 9, 'evermore.jpg', '2020-12-11', 'English', NULL, NULL, NULL, NOW()),
('gold rush', 1, 4, 9, 'evermore.jpg', '2020-12-11', 'English', NULL, NULL, NULL, NOW()),
('''tis the damn season', 1, 4, 9, 'evermore.jpg', '2020-12-11', 'English', NULL, NULL, NULL, NOW()),
('tolerate it', 1, 4, 9, 'evermore.jpg', '2020-12-11', 'English', NULL, NULL, NULL, NOW()),
('no body, no crime', 1, 4, 9, 'evermore.jpg', '2020-12-11', 'English', NULL, NULL, NULL, NOW()),
('happiness', 1, 4, 9, 'evermore.jpg', '2020-12-11', 'English', NULL, NULL, NULL, NOW()),
('dorothea', 1, 4, 9, 'evermore.jpg', '2020-12-11', 'English', NULL, NULL, NULL, NOW()),
('coney island', 1, 4, 9, 'evermore.jpg', '2020-12-11', 'English', NULL, NULL, NULL, NOW()),
('ivy', 1, 4, 9, 'evermore.jpg', '2020-12-11', 'English', NULL, NULL, NULL, NOW()),
('cowboy like me', 1, 4, 9, 'evermore.jpg', '2020-12-11', 'English', NULL, NULL, NULL, NOW()),
('long story short', 1, 4, 9, 'evermore.jpg', '2020-12-11', 'English', NULL, NULL, NULL, NOW()),
('marjorie', 1, 4, 9, 'evermore.jpg', '2020-12-11', 'English', NULL, NULL, NULL, NOW()),
('closure', 1, 4, 9, 'evermore.jpg', '2020-12-11', 'English', NULL, NULL, NULL, NOW()),
('evermore', 1, 4, 9, 'evermore.jpg', '2020-12-11', 'English', NULL, NULL, NULL, NOW()),

('Lavender Haze', 1, 2, 10, 'midnights.jpg', '2022-10-21', 'English', NULL, NULL, NULL, NOW()),
('Maroon', 1, 2, 10, 'midnights.jpg', '2022-10-21', 'English', NULL, NULL, NULL, NOW()),
('Anti-Hero', 1, 2, 10, 'midnights.jpg', '2022-10-21', 'English', NULL, NULL, NULL, NOW()),
('Snow On The Beach', 1, 2, 10, 'midnights.jpg', '2022-10-21', 'English', NULL, NULL, NULL, NOW()),
('You''re on Your Own, Kid', 1, 2, 10, 'midnights.jpg', '2022-10-21', 'English', NULL, NULL, NULL, NOW()),
('Midnight Rain', 1, 2, 10, 'midnights.jpg', '2022-10-21', 'English', NULL, NULL, NULL, NOW()),
('Question...?', 1, 2, 10, 'midnights.jpg', '2022-10-21', 'English', NULL, NULL, NULL, NOW()),
('Vigilante Shit', 1, 2, 10, 'midnights.jpg', '2022-10-21', 'English', NULL, NULL, NULL, NOW()),
('Bejeweled', 1, 2, 10, 'midnights.jpg', '2022-10-21', 'English', NULL, NULL, NULL, NOW()),
('Labyrinth', 1, 2, 10, 'midnights.jpg', '2022-10-21', 'English', NULL, NULL, NULL, NOW()),
('Karma', 1, 2, 10, 'midnights.jpg', '2022-10-21', 'English', NULL, NULL, NULL, NOW()),
('Sweet Nothing', 1, 2, 10, 'midnights.jpg', '2022-10-21', 'English', NULL, NULL, NULL, NOW()),
('Mastermind', 1, 2, 10, 'midnights.jpg', '2022-10-21', 'English', NULL, NULL, NULL, NOW()),

('Fortnight', 1, 3, 11, 'tortured_poets.jpg', '2024-04-19', 'English', NULL, NULL, NULL, NOW()),
('The Tortured Poets Department', 1, 3, 11, 'tortured_poets.jpg', '2024-04-19', 'English', NULL, NULL, NULL, NOW()),
('My Boy Only Breaks His Favorite Toys', 1, 3, 11, 'tortured_poets.jpg', '2024-04-19', 'English', NULL, NULL, NULL, NOW()),
('Down Bad', 1, 3, 11, 'tortured_poets.jpg', '2024-04-19', 'English', NULL, NULL, NULL, NOW()),
('So Long, London', 1, 3, 11, 'tortured_poets.jpg', '2024-04-19', 'English', NULL, NULL, NULL, NOW()),
('But Daddy I Love Him', 1, 3, 11, 'tortured_poets.jpg', '2024-04-19', 'English', NULL, NULL, NULL, NOW()),
('Fresh Out the Slammer', 1, 3, 11, 'tortured_poets.jpg', '2024-04-19', 'English', NULL, NULL, NULL, NOW()),
('Florida!!!', 1, 3, 11, 'tortured_poets.jpg', '2024-04-19', 'English', NULL, NULL, NULL, NOW()),
('Guilty as Sin?', 1, 3, 11, 'tortured_poets.jpg', '2024-04-19', 'English', NULL, NULL, NULL, NOW()),
('Who''s Afraid of Little Old Me?', 1, 3, 11, 'tortured_poets.jpg', '2024-04-19', 'English', NULL, NULL, NULL, NOW()),
('I Can Fix Him (No Really I Can)', 1, 3, 11, 'tortured_poets.jpg', '2024-04-19', 'English', NULL, NULL, NULL, NOW()),
('loml', 1, 3, 11, 'tortured_poets.jpg', '2024-04-19', 'English', NULL, NULL, NULL, NOW()),
('I Can Do It With a Broken Heart', 1, 3, 11, 'tortured_poets.jpg', '2024-04-19', 'English', NULL, NULL, NULL, NOW()),
('The Smallest Man Who Ever Lived', 1, 3, 11, 'tortured_poets.jpg', '2024-04-19', 'English', NULL, NULL, NULL, NOW()),
('The Alchemy', 1, 3, 11, 'tortured_poets.jpg', '2024-04-19', 'English', NULL, NULL, NULL, NOW()),
('Clara Bow', 1, 3, 11, 'tortured_poets.jpg', '2024-04-19', 'English', NULL, NULL, NULL, NOW()),

('The Fate of Ophelia', 1, 2, 12, 'showgirl.jpg', '2025-10-03', 'English', NULL, NULL, NULL, NOW()),
('Elizabeth Taylor', 1, 2, 12, 'showgirl.jpg', '2025-10-03', 'English', NULL, NULL, NULL, NOW()),
('Opalite', 1, 2, 12, 'showgirl.jpg', '2025-10-03', 'English', NULL, NULL, NULL, NOW()),
('Father Figure', 1, 2, 12, 'showgirl.jpg', '2025-10-03', 'English', NULL, NULL, NULL, NOW()),
('Eldest Daughter', 1, 2, 12, 'showgirl.jpg', '2025-10-03', 'English', NULL, NULL, NULL, NOW()),
('Ruin the Friendship', 1, 2, 12, 'showgirl.jpg', '2025-10-03', 'English', NULL, NULL, NULL, NOW()),
('Actually Romantic', 1, 2, 12, 'showgirl.jpg', '2025-10-03', 'English', NULL, NULL, NULL, NOW()),
('Wi$h Li$t', 1, 2, 12, 'showgirl.jpg', '2025-10-03', 'English', NULL, NULL, NULL, NOW()),
('Wood', 1, 2, 12, 'showgirl.jpg', '2025-10-03', 'English', NULL, NULL, NULL, NOW()),
('CANCELLED!', 1, 2, 12, 'showgirl.jpg', '2025-10-03', 'English', NULL, NULL, NULL, NOW()),
('Honey', 1, 2, 12, 'showgirl.jpg', '2025-10-03', 'English', NULL, NULL, NULL, NOW()),
('The Life of a Showgirl', 1, 2, 12, 'showgirl.jpg', '2025-10-03', 'English', NULL, NULL, NULL, NOW());

ALTER TABLE genres AUTO_INCREMENT = 13;
ALTER TABLE artists AUTO_INCREMENT = 2;
ALTER TABLE albums AUTO_INCREMENT = 13;
