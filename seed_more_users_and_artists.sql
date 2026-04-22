USE nln_lyrics;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO users (username, email, password_hash, full_name, role, avatar, created_at, updated_at)
VALUES
('user01', 'user01@nln.local', 'user01123', 'User 01', 'user', NULL, NOW(), NOW()),
('user02', 'user02@nln.local', 'user02123', 'User 02', 'user', NULL, NOW(), NOW()),
('user03', 'user03@nln.local', 'user03123', 'User 03', 'user', NULL, NOW(), NOW()),
('user04', 'user04@nln.local', 'user04123', 'User 04', 'user', NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE
full_name = VALUES(full_name),
password_hash = VALUES(password_hash),
updated_at = NOW();

DELETE FROM songs
WHERE artist_id IN (
    SELECT artist_id FROM (
        SELECT artist_id
        FROM artists
        WHERE artist_name IN ('Olivia Rodrigo', 'Ariana Grande', 'Adele', 'Katy Perry')
    ) AS x
);

DELETE FROM albums
WHERE artist_id IN (
    SELECT artist_id FROM (
        SELECT artist_id
        FROM artists
        WHERE artist_name IN ('Olivia Rodrigo', 'Ariana Grande', 'Adele', 'Katy Perry')
    ) AS x
);

DELETE FROM artists
WHERE artist_name IN ('Olivia Rodrigo', 'Ariana Grande', 'Adele', 'Katy Perry');

INSERT IGNORE INTO genres (genre_id, genre_name) VALUES
(5, 'R&B Pop'),
(6, 'Soul Pop'),
(7, 'Dance Pop');

INSERT INTO artists (artist_id, artist_name, avatar, bio, country, birth_year, created_at) VALUES
(2, 'Olivia Rodrigo', 'olivia_rodgiro.jfif', 'American singer-songwriter recognized for confessional pop songwriting.', 'USA', 2003, NOW()),
(3, 'Ariana Grande', 'ariana_grande.webp', 'American pop and R&B singer with a wide vocal range and strong chart presence.', 'USA', 1993, NOW()),
(4, 'Adele', 'adele.jfif', 'British singer-songwriter known for emotionally direct ballads and powerful vocals.', 'UK', 1988, NOW()),
(5, 'Katy Perry', 'katy_perry.jfif', 'American pop artist known for bright hooks, theatrical visuals and radio pop anthems.', 'USA', 1984, NOW());

INSERT INTO albums (album_id, album_name, artist_id, cover_image, release_year, release_date, created_at) VALUES
(13, 'SOUR', 2, 'Sour.jfif', 2021, '2021-05-21', NOW()),
(14, 'GUTS', 2, 'olivia_rodgiro.jfif', 2023, '2023-09-08', NOW()),

(15, 'Yours Truly', 3, 'ariana_grande.webp', 2013, '2013-08-30', NOW()),
(16, 'My Everything', 3, 'ariana_grande.webp', 2014, '2014-08-25', NOW()),
(17, 'Dangerous Woman', 3, 'ariana_grande.webp', 2016, '2016-05-20', NOW()),
(18, 'Sweetener', 3, 'ariana_grande.webp', 2018, '2018-08-17', NOW()),
(19, 'thank u, next', 3, 'ariana_grande.webp', 2019, '2019-02-08', NOW()),
(20, 'Positions', 3, 'ariana_grande.webp', 2020, '2020-10-30', NOW()),
(21, 'eternal sunshine', 3, 'ariana_grande.webp', 2024, '2024-03-08', NOW()),

(22, '19', 4, 'adele.jfif', 2008, '2008-01-28', NOW()),
(23, '21', 4, 'adele.jfif', 2011, '2011-01-24', NOW()),
(24, '25', 4, 'adele.jfif', 2015, '2015-11-20', NOW()),
(25, '30', 4, 'adele.jfif', 2021, '2021-11-19', NOW()),

(26, 'One of the Boys', 5, 'katy_perry.jfif', 2008, '2008-06-17', NOW()),
(27, 'Teenage Dream', 5, 'katy_perry.jfif', 2010, '2010-08-24', NOW()),
(28, 'PRISM', 5, 'katy_perry.jfif', 2013, '2013-10-18', NOW()),
(29, 'Witness', 5, 'katy_perry.jfif', 2017, '2017-06-09', NOW()),
(30, 'Smile', 5, 'katy_perry.jfif', 2020, '2020-08-28', NOW()),
(31, '143', 5, 'katy_perry.jfif', 2024, '2024-09-20', NOW());

INSERT INTO songs (title, artist_id, genre_id, album_id, cover_image, release_date, language, lyrics, meaning, youtube_video_id, created_at) VALUES
('brutal', 2, 2, 13, 'Sour.jfif', '2021-05-21', 'English', NULL, NULL, NULL, NOW()),
('traitor', 2, 2, 13, 'Sour.jfif', '2021-05-21', 'English', NULL, NULL, NULL, NOW()),
('drivers license', 2, 2, 13, 'Sour.jfif', '2021-05-21', 'English', NULL, NULL, NULL, NOW()),
('1 step forward, 3 steps back', 2, 2, 13, 'Sour.jfif', '2021-05-21', 'English', NULL, NULL, NULL, NOW()),
('deja vu', 2, 2, 13, 'Sour.jfif', '2021-05-21', 'English', NULL, NULL, NULL, NOW()),
('good 4 u', 2, 2, 13, 'Sour.jfif', '2021-05-21', 'English', NULL, NULL, NULL, NOW()),
('enough for you', 2, 2, 13, 'Sour.jfif', '2021-05-21', 'English', NULL, NULL, NULL, NOW()),
('happier', 2, 2, 13, 'Sour.jfif', '2021-05-21', 'English', NULL, NULL, NULL, NOW()),
('jealousy, jealousy', 2, 2, 13, 'Sour.jfif', '2021-05-21', 'English', NULL, NULL, NULL, NOW()),
('favorite crime', 2, 2, 13, 'Sour.jfif', '2021-05-21', 'English', NULL, NULL, NULL, NOW()),
('hope ur ok', 2, 2, 13, 'Sour.jfif', '2021-05-21', 'English', NULL, NULL, NULL, NOW()),

('all-american bitch', 2, 2, 14, 'olivia_rodgiro.jfif', '2023-09-08', 'English', NULL, NULL, NULL, NOW()),
('bad idea right?', 2, 2, 14, 'olivia_rodgiro.jfif', '2023-09-08', 'English', NULL, NULL, NULL, NOW()),
('vampire', 2, 2, 14, 'olivia_rodgiro.jfif', '2023-09-08', 'English', NULL, NULL, NULL, NOW()),
('lacy', 2, 2, 14, 'olivia_rodgiro.jfif', '2023-09-08', 'English', NULL, NULL, NULL, NOW()),
('ballad of a homeschooled girl', 2, 2, 14, 'olivia_rodgiro.jfif', '2023-09-08', 'English', NULL, NULL, NULL, NOW()),
('making the bed', 2, 2, 14, 'olivia_rodgiro.jfif', '2023-09-08', 'English', NULL, NULL, NULL, NOW()),
('logical', 2, 2, 14, 'olivia_rodgiro.jfif', '2023-09-08', 'English', NULL, NULL, NULL, NOW()),
('get him back!', 2, 2, 14, 'olivia_rodgiro.jfif', '2023-09-08', 'English', NULL, NULL, NULL, NOW()),
('love is embarrassing', 2, 2, 14, 'olivia_rodgiro.jfif', '2023-09-08', 'English', NULL, NULL, NULL, NOW()),
('the grudge', 2, 2, 14, 'olivia_rodgiro.jfif', '2023-09-08', 'English', NULL, NULL, NULL, NOW()),
('pretty isn''t pretty', 2, 2, 14, 'olivia_rodgiro.jfif', '2023-09-08', 'English', NULL, NULL, NULL, NOW()),
('teenage dream', 2, 2, 14, 'olivia_rodgiro.jfif', '2023-09-08', 'English', NULL, NULL, NULL, NOW()),

('Honeymoon Avenue', 3, 5, 15, 'ariana_grande.webp', '2013-08-30', 'English', NULL, NULL, NULL, NOW()),
('Baby I', 3, 5, 15, 'ariana_grande.webp', '2013-08-30', 'English', NULL, NULL, NULL, NOW()),
('Right There', 3, 5, 15, 'ariana_grande.webp', '2013-08-30', 'English', NULL, NULL, NULL, NOW()),
('Tattooed Heart', 3, 5, 15, 'ariana_grande.webp', '2013-08-30', 'English', NULL, NULL, NULL, NOW()),
('Lovin'' It', 3, 5, 15, 'ariana_grande.webp', '2013-08-30', 'English', NULL, NULL, NULL, NOW()),
('Piano', 3, 5, 15, 'ariana_grande.webp', '2013-08-30', 'English', NULL, NULL, NULL, NOW()),
('Daydreamin''', 3, 5, 15, 'ariana_grande.webp', '2013-08-30', 'English', NULL, NULL, NULL, NOW()),
('The Way', 3, 5, 15, 'ariana_grande.webp', '2013-08-30', 'English', NULL, NULL, NULL, NOW()),
('You''ll Never Know', 3, 5, 15, 'ariana_grande.webp', '2013-08-30', 'English', NULL, NULL, NULL, NOW()),
('Almost Is Never Enough', 3, 5, 15, 'ariana_grande.webp', '2013-08-30', 'English', NULL, NULL, NULL, NOW()),
('Popular Song', 3, 5, 15, 'ariana_grande.webp', '2013-08-30', 'English', NULL, NULL, NULL, NOW()),
('Better Left Unsaid', 3, 5, 15, 'ariana_grande.webp', '2013-08-30', 'English', NULL, NULL, NULL, NOW()),

('Intro', 3, 2, 16, 'ariana_grande.webp', '2014-08-25', 'English', NULL, NULL, NULL, NOW()),
('Problem', 3, 2, 16, 'ariana_grande.webp', '2014-08-25', 'English', NULL, NULL, NULL, NOW()),
('One Last Time', 3, 2, 16, 'ariana_grande.webp', '2014-08-25', 'English', NULL, NULL, NULL, NOW()),
('Why Try', 3, 2, 16, 'ariana_grande.webp', '2014-08-25', 'English', NULL, NULL, NULL, NOW()),
('Break Free', 3, 2, 16, 'ariana_grande.webp', '2014-08-25', 'English', NULL, NULL, NULL, NOW()),
('Best Mistake', 3, 2, 16, 'ariana_grande.webp', '2014-08-25', 'English', NULL, NULL, NULL, NOW()),
('Be My Baby', 3, 2, 16, 'ariana_grande.webp', '2014-08-25', 'English', NULL, NULL, NULL, NOW()),
('Break Your Heart Right Back', 3, 2, 16, 'ariana_grande.webp', '2014-08-25', 'English', NULL, NULL, NULL, NOW()),
('Love Me Harder', 3, 2, 16, 'ariana_grande.webp', '2014-08-25', 'English', NULL, NULL, NULL, NOW()),
('Just a Little Bit of Your Heart', 3, 2, 16, 'ariana_grande.webp', '2014-08-25', 'English', NULL, NULL, NULL, NOW()),
('Hands on Me', 3, 2, 16, 'ariana_grande.webp', '2014-08-25', 'English', NULL, NULL, NULL, NOW()),
('My Everything', 3, 2, 16, 'ariana_grande.webp', '2014-08-25', 'English', NULL, NULL, NULL, NOW()),

('Moonlight', 3, 2, 17, 'ariana_grande.webp', '2016-05-20', 'English', NULL, NULL, NULL, NOW()),
('Dangerous Woman', 3, 2, 17, 'ariana_grande.webp', '2016-05-20', 'English', NULL, NULL, NULL, NOW()),
('Be Alright', 3, 2, 17, 'ariana_grande.webp', '2016-05-20', 'English', NULL, NULL, NULL, NOW()),
('Into You', 3, 2, 17, 'ariana_grande.webp', '2016-05-20', 'English', NULL, NULL, NULL, NOW()),
('Side to Side', 3, 2, 17, 'ariana_grande.webp', '2016-05-20', 'English', NULL, NULL, NULL, NOW()),
('Let Me Love You', 3, 2, 17, 'ariana_grande.webp', '2016-05-20', 'English', NULL, NULL, NULL, NOW()),
('Greedy', 3, 2, 17, 'ariana_grande.webp', '2016-05-20', 'English', NULL, NULL, NULL, NOW()),
('Leave Me Lonely', 3, 2, 17, 'ariana_grande.webp', '2016-05-20', 'English', NULL, NULL, NULL, NOW()),
('Everyday', 3, 2, 17, 'ariana_grande.webp', '2016-05-20', 'English', NULL, NULL, NULL, NOW()),
('Sometimes', 3, 2, 17, 'ariana_grande.webp', '2016-05-20', 'English', NULL, NULL, NULL, NOW()),
('I Don''t Care', 3, 2, 17, 'ariana_grande.webp', '2016-05-20', 'English', NULL, NULL, NULL, NOW()),

('raindrops (an angel cried)', 3, 5, 18, 'ariana_grande.webp', '2018-08-17', 'English', NULL, NULL, NULL, NOW()),
('blazed', 3, 5, 18, 'ariana_grande.webp', '2018-08-17', 'English', NULL, NULL, NULL, NOW()),
('the light is coming', 3, 5, 18, 'ariana_grande.webp', '2018-08-17', 'English', NULL, NULL, NULL, NOW()),
('R.E.M.', 3, 5, 18, 'ariana_grande.webp', '2018-08-17', 'English', NULL, NULL, NULL, NOW()),
('God is a woman', 3, 5, 18, 'ariana_grande.webp', '2018-08-17', 'English', NULL, NULL, NULL, NOW()),
('sweetener', 3, 5, 18, 'ariana_grande.webp', '2018-08-17', 'English', NULL, NULL, NULL, NOW()),
('successful', 3, 5, 18, 'ariana_grande.webp', '2018-08-17', 'English', NULL, NULL, NULL, NOW()),
('everytime', 3, 5, 18, 'ariana_grande.webp', '2018-08-17', 'English', NULL, NULL, NULL, NOW()),
('breathin', 3, 5, 18, 'ariana_grande.webp', '2018-08-17', 'English', NULL, NULL, NULL, NOW()),
('no tears left to cry', 3, 5, 18, 'ariana_grande.webp', '2018-08-17', 'English', NULL, NULL, NULL, NOW()),
('borderline', 3, 5, 18, 'ariana_grande.webp', '2018-08-17', 'English', NULL, NULL, NULL, NOW()),
('better off', 3, 5, 18, 'ariana_grande.webp', '2018-08-17', 'English', NULL, NULL, NULL, NOW()),
('goodnight n go', 3, 5, 18, 'ariana_grande.webp', '2018-08-17', 'English', NULL, NULL, NULL, NOW()),
('pete davidson', 3, 5, 18, 'ariana_grande.webp', '2018-08-17', 'English', NULL, NULL, NULL, NOW()),
('get well soon', 3, 5, 18, 'ariana_grande.webp', '2018-08-17', 'English', NULL, NULL, NULL, NOW()),

('imagine', 3, 2, 19, 'ariana_grande.webp', '2019-02-08', 'English', NULL, NULL, NULL, NOW()),
('needy', 3, 2, 19, 'ariana_grande.webp', '2019-02-08', 'English', NULL, NULL, NULL, NOW()),
('NASA', 3, 2, 19, 'ariana_grande.webp', '2019-02-08', 'English', NULL, NULL, NULL, NOW()),
('bloodline', 3, 2, 19, 'ariana_grande.webp', '2019-02-08', 'English', NULL, NULL, NULL, NOW()),
('fake smile', 3, 2, 19, 'ariana_grande.webp', '2019-02-08', 'English', NULL, NULL, NULL, NOW()),
('bad idea', 3, 2, 19, 'ariana_grande.webp', '2019-02-08', 'English', NULL, NULL, NULL, NOW()),
('make up', 3, 2, 19, 'ariana_grande.webp', '2019-02-08', 'English', NULL, NULL, NULL, NOW()),
('ghostin', 3, 2, 19, 'ariana_grande.webp', '2019-02-08', 'English', NULL, NULL, NULL, NOW()),
('in my head', 3, 2, 19, 'ariana_grande.webp', '2019-02-08', 'English', NULL, NULL, NULL, NOW()),
('7 rings', 3, 2, 19, 'ariana_grande.webp', '2019-02-08', 'English', NULL, NULL, NULL, NOW()),
('thank u, next', 3, 2, 19, 'ariana_grande.webp', '2019-02-08', 'English', NULL, NULL, NULL, NOW()),
('break up with your girlfriend, i''m bored', 3, 2, 19, 'ariana_grande.webp', '2019-02-08', 'English', NULL, NULL, NULL, NOW()),

('shut up', 3, 2, 20, 'ariana_grande.webp', '2020-10-30', 'English', NULL, NULL, NULL, NOW()),
('34+35', 3, 2, 20, 'ariana_grande.webp', '2020-10-30', 'English', NULL, NULL, NULL, NOW()),
('motive', 3, 2, 20, 'ariana_grande.webp', '2020-10-30', 'English', NULL, NULL, NULL, NOW()),
('just like magic', 3, 2, 20, 'ariana_grande.webp', '2020-10-30', 'English', NULL, NULL, NULL, NOW()),
('off the table', 3, 2, 20, 'ariana_grande.webp', '2020-10-30', 'English', NULL, NULL, NULL, NOW()),
('six thirty', 3, 2, 20, 'ariana_grande.webp', '2020-10-30', 'English', NULL, NULL, NULL, NOW()),
('safety net', 3, 2, 20, 'ariana_grande.webp', '2020-10-30', 'English', NULL, NULL, NULL, NOW()),
('my hair', 3, 2, 20, 'ariana_grande.webp', '2020-10-30', 'English', NULL, NULL, NULL, NOW()),
('nasty', 3, 2, 20, 'ariana_grande.webp', '2020-10-30', 'English', NULL, NULL, NULL, NOW()),
('west side', 3, 2, 20, 'ariana_grande.webp', '2020-10-30', 'English', NULL, NULL, NULL, NOW()),
('love language', 3, 2, 20, 'ariana_grande.webp', '2020-10-30', 'English', NULL, NULL, NULL, NOW()),
('positions', 3, 2, 20, 'ariana_grande.webp', '2020-10-30', 'English', NULL, NULL, NULL, NOW()),
('obvious', 3, 2, 20, 'ariana_grande.webp', '2020-10-30', 'English', NULL, NULL, NULL, NOW()),
('pov', 3, 2, 20, 'ariana_grande.webp', '2020-10-30', 'English', NULL, NULL, NULL, NOW()),

('intro (end of the world)', 3, 2, 21, 'ariana_grande.webp', '2024-03-08', 'English', NULL, NULL, NULL, NOW()),
('bye', 3, 2, 21, 'ariana_grande.webp', '2024-03-08', 'English', NULL, NULL, NULL, NOW()),
('don''t wanna break up again', 3, 2, 21, 'ariana_grande.webp', '2024-03-08', 'English', NULL, NULL, NULL, NOW()),
('saturn returns interlude', 3, 2, 21, 'ariana_grande.webp', '2024-03-08', 'English', NULL, NULL, NULL, NOW()),
('eternal sunshine', 3, 2, 21, 'ariana_grande.webp', '2024-03-08', 'English', NULL, NULL, NULL, NOW()),
('supernatural', 3, 2, 21, 'ariana_grande.webp', '2024-03-08', 'English', NULL, NULL, NULL, NOW()),
('true story', 3, 2, 21, 'ariana_grande.webp', '2024-03-08', 'English', NULL, NULL, NULL, NOW()),
('the boy is mine', 3, 2, 21, 'ariana_grande.webp', '2024-03-08', 'English', NULL, NULL, NULL, NOW()),
('yes, and?', 3, 2, 21, 'ariana_grande.webp', '2024-03-08', 'English', NULL, NULL, NULL, NOW()),
('we can''t be friends (wait for your love)', 3, 2, 21, 'ariana_grande.webp', '2024-03-08', 'English', NULL, NULL, NULL, NOW()),
('i wish i hated you', 3, 2, 21, 'ariana_grande.webp', '2024-03-08', 'English', NULL, NULL, NULL, NOW()),
('imperfect for you', 3, 2, 21, 'ariana_grande.webp', '2024-03-08', 'English', NULL, NULL, NULL, NOW()),
('ordinary things', 3, 2, 21, 'ariana_grande.webp', '2024-03-08', 'English', NULL, NULL, NULL, NOW()),

('Daydreamer', 4, 6, 22, 'adele.jfif', '2008-01-28', 'English', NULL, NULL, NULL, NOW()),
('Best for Last', 4, 6, 22, 'adele.jfif', '2008-01-28', 'English', NULL, NULL, NULL, NOW()),
('Chasing Pavements', 4, 6, 22, 'adele.jfif', '2008-01-28', 'English', NULL, NULL, NULL, NOW()),
('Cold Shoulder', 4, 6, 22, 'adele.jfif', '2008-01-28', 'English', NULL, NULL, NULL, NOW()),
('Crazy for You', 4, 6, 22, 'adele.jfif', '2008-01-28', 'English', NULL, NULL, NULL, NOW()),
('Melt My Heart to Stone', 4, 6, 22, 'adele.jfif', '2008-01-28', 'English', NULL, NULL, NULL, NOW()),
('First Love', 4, 6, 22, 'adele.jfif', '2008-01-28', 'English', NULL, NULL, NULL, NOW()),
('Right as Rain', 4, 6, 22, 'adele.jfif', '2008-01-28', 'English', NULL, NULL, NULL, NOW()),
('Make You Feel My Love', 4, 6, 22, 'adele.jfif', '2008-01-28', 'English', NULL, NULL, NULL, NOW()),
('My Same', 4, 6, 22, 'adele.jfif', '2008-01-28', 'English', NULL, NULL, NULL, NOW()),
('Tired', 4, 6, 22, 'adele.jfif', '2008-01-28', 'English', NULL, NULL, NULL, NOW()),
('Hometown Glory', 4, 6, 22, 'adele.jfif', '2008-01-28', 'English', NULL, NULL, NULL, NOW()),

('Rolling in the Deep', 4, 6, 23, 'adele.jfif', '2011-01-24', 'English', NULL, NULL, NULL, NOW()),
('Rumour Has It', 4, 6, 23, 'adele.jfif', '2011-01-24', 'English', NULL, NULL, NULL, NOW()),
('Turning Tables', 4, 6, 23, 'adele.jfif', '2011-01-24', 'English', NULL, NULL, NULL, NOW()),
('Don''t You Remember', 4, 6, 23, 'adele.jfif', '2011-01-24', 'English', NULL, NULL, NULL, NOW()),
('Set Fire to the Rain', 4, 6, 23, 'adele.jfif', '2011-01-24', 'English', NULL, NULL, NULL, NOW()),
('He Won''t Go', 4, 6, 23, 'adele.jfif', '2011-01-24', 'English', NULL, NULL, NULL, NOW()),
('Take It All', 4, 6, 23, 'adele.jfif', '2011-01-24', 'English', NULL, NULL, NULL, NOW()),
('I''ll Be Waiting', 4, 6, 23, 'adele.jfif', '2011-01-24', 'English', NULL, NULL, NULL, NOW()),
('One and Only', 4, 6, 23, 'adele.jfif', '2011-01-24', 'English', NULL, NULL, NULL, NOW()),
('Lovesong', 4, 6, 23, 'adele.jfif', '2011-01-24', 'English', NULL, NULL, NULL, NOW()),
('Someone Like You', 4, 6, 23, 'adele.jfif', '2011-01-24', 'English', NULL, NULL, NULL, NOW()),

('Hello', 4, 6, 24, 'adele.jfif', '2015-11-20', 'English', NULL, NULL, NULL, NOW()),
('Send My Love (To Your New Lover)', 4, 6, 24, 'adele.jfif', '2015-11-20', 'English', NULL, NULL, NULL, NOW()),
('I Miss You', 4, 6, 24, 'adele.jfif', '2015-11-20', 'English', NULL, NULL, NULL, NOW()),
('When We Were Young', 4, 6, 24, 'adele.jfif', '2015-11-20', 'English', NULL, NULL, NULL, NOW()),
('Remedy', 4, 6, 24, 'adele.jfif', '2015-11-20', 'English', NULL, NULL, NULL, NOW()),
('Water Under the Bridge', 4, 6, 24, 'adele.jfif', '2015-11-20', 'English', NULL, NULL, NULL, NOW()),
('River Lea', 4, 6, 24, 'adele.jfif', '2015-11-20', 'English', NULL, NULL, NULL, NOW()),
('Love in the Dark', 4, 6, 24, 'adele.jfif', '2015-11-20', 'English', NULL, NULL, NULL, NOW()),
('Million Years Ago', 4, 6, 24, 'adele.jfif', '2015-11-20', 'English', NULL, NULL, NULL, NOW()),
('All I Ask', 4, 6, 24, 'adele.jfif', '2015-11-20', 'English', NULL, NULL, NULL, NOW()),
('Sweetest Devotion', 4, 6, 24, 'adele.jfif', '2015-11-20', 'English', NULL, NULL, NULL, NOW()),

('Strangers by Nature', 4, 6, 25, 'adele.jfif', '2021-11-19', 'English', NULL, NULL, NULL, NOW()),
('Easy On Me', 4, 6, 25, 'adele.jfif', '2021-11-19', 'English', NULL, NULL, NULL, NOW()),
('My Little Love', 4, 6, 25, 'adele.jfif', '2021-11-19', 'English', NULL, NULL, NULL, NOW()),
('Cry Your Heart Out', 4, 6, 25, 'adele.jfif', '2021-11-19', 'English', NULL, NULL, NULL, NOW()),
('Oh My God', 4, 6, 25, 'adele.jfif', '2021-11-19', 'English', NULL, NULL, NULL, NOW()),
('Can I Get It', 4, 6, 25, 'adele.jfif', '2021-11-19', 'English', NULL, NULL, NULL, NOW()),
('I Drink Wine', 4, 6, 25, 'adele.jfif', '2021-11-19', 'English', NULL, NULL, NULL, NOW()),
('All Night Parking', 4, 6, 25, 'adele.jfif', '2021-11-19', 'English', NULL, NULL, NULL, NOW()),
('Woman Like Me', 4, 6, 25, 'adele.jfif', '2021-11-19', 'English', NULL, NULL, NULL, NOW()),
('Hold On', 4, 6, 25, 'adele.jfif', '2021-11-19', 'English', NULL, NULL, NULL, NOW()),
('To Be Loved', 4, 6, 25, 'adele.jfif', '2021-11-19', 'English', NULL, NULL, NULL, NOW()),
('Love Is a Game', 4, 6, 25, 'adele.jfif', '2021-11-19', 'English', NULL, NULL, NULL, NOW()),

('One of the Boys', 5, 7, 26, 'katy_perry.jfif', '2008-06-17', 'English', NULL, NULL, NULL, NOW()),
('I Kissed a Girl', 5, 7, 26, 'katy_perry.jfif', '2008-06-17', 'English', NULL, NULL, NULL, NOW()),
('Waking Up in Vegas', 5, 7, 26, 'katy_perry.jfif', '2008-06-17', 'English', NULL, NULL, NULL, NOW()),
('Thinking of You', 5, 7, 26, 'katy_perry.jfif', '2008-06-17', 'English', NULL, NULL, NULL, NOW()),
('Mannequin', 5, 7, 26, 'katy_perry.jfif', '2008-06-17', 'English', NULL, NULL, NULL, NOW()),
('Ur So Gay', 5, 7, 26, 'katy_perry.jfif', '2008-06-17', 'English', NULL, NULL, NULL, NOW()),
('Hot n Cold', 5, 7, 26, 'katy_perry.jfif', '2008-06-17', 'English', NULL, NULL, NULL, NOW()),
('If You Can Afford Me', 5, 7, 26, 'katy_perry.jfif', '2008-06-17', 'English', NULL, NULL, NULL, NOW()),
('Lost', 5, 7, 26, 'katy_perry.jfif', '2008-06-17', 'English', NULL, NULL, NULL, NOW()),
('Self Inflicted', 5, 7, 26, 'katy_perry.jfif', '2008-06-17', 'English', NULL, NULL, NULL, NOW()),
('I''m Still Breathing', 5, 7, 26, 'katy_perry.jfif', '2008-06-17', 'English', NULL, NULL, NULL, NOW()),
('Fingerprints', 5, 7, 26, 'katy_perry.jfif', '2008-06-17', 'English', NULL, NULL, NULL, NOW()),

('Teenage Dream', 5, 7, 27, 'katy_perry.jfif', '2010-08-24', 'English', NULL, NULL, NULL, NOW()),
('Last Friday Night (T.G.I.F.)', 5, 7, 27, 'katy_perry.jfif', '2010-08-24', 'English', NULL, NULL, NULL, NOW()),
('California Gurls', 5, 7, 27, 'katy_perry.jfif', '2010-08-24', 'English', NULL, NULL, NULL, NOW()),
('Firework', 5, 7, 27, 'katy_perry.jfif', '2010-08-24', 'English', NULL, NULL, NULL, NOW()),
('Peacock', 5, 7, 27, 'katy_perry.jfif', '2010-08-24', 'English', NULL, NULL, NULL, NOW()),
('Circle the Drain', 5, 7, 27, 'katy_perry.jfif', '2010-08-24', 'English', NULL, NULL, NULL, NOW()),
('The One That Got Away', 5, 7, 27, 'katy_perry.jfif', '2010-08-24', 'English', NULL, NULL, NULL, NOW()),
('E.T.', 5, 7, 27, 'katy_perry.jfif', '2010-08-24', 'English', NULL, NULL, NULL, NOW()),
('Who Am I Living For?', 5, 7, 27, 'katy_perry.jfif', '2010-08-24', 'English', NULL, NULL, NULL, NOW()),
('Pearl', 5, 7, 27, 'katy_perry.jfif', '2010-08-24', 'English', NULL, NULL, NULL, NOW()),
('Hummingbird Heartbeat', 5, 7, 27, 'katy_perry.jfif', '2010-08-24', 'English', NULL, NULL, NULL, NOW()),
('Not Like the Movies', 5, 7, 27, 'katy_perry.jfif', '2010-08-24', 'English', NULL, NULL, NULL, NOW()),

('Roar', 5, 7, 28, 'katy_perry.jfif', '2013-10-18', 'English', NULL, NULL, NULL, NOW()),
('Legendary Lovers', 5, 7, 28, 'katy_perry.jfif', '2013-10-18', 'English', NULL, NULL, NULL, NOW()),
('Birthday', 5, 7, 28, 'katy_perry.jfif', '2013-10-18', 'English', NULL, NULL, NULL, NOW()),
('Walking on Air', 5, 7, 28, 'katy_perry.jfif', '2013-10-18', 'English', NULL, NULL, NULL, NOW()),
('Unconditionally', 5, 7, 28, 'katy_perry.jfif', '2013-10-18', 'English', NULL, NULL, NULL, NOW()),
('Dark Horse', 5, 7, 28, 'katy_perry.jfif', '2013-10-18', 'English', NULL, NULL, NULL, NOW()),
('This Is How We Do', 5, 7, 28, 'katy_perry.jfif', '2013-10-18', 'English', NULL, NULL, NULL, NOW()),
('International Smile', 5, 7, 28, 'katy_perry.jfif', '2013-10-18', 'English', NULL, NULL, NULL, NOW()),
('Ghost', 5, 7, 28, 'katy_perry.jfif', '2013-10-18', 'English', NULL, NULL, NULL, NOW()),
('Love Me', 5, 7, 28, 'katy_perry.jfif', '2013-10-18', 'English', NULL, NULL, NULL, NOW()),
('This Moment', 5, 7, 28, 'katy_perry.jfif', '2013-10-18', 'English', NULL, NULL, NULL, NOW()),
('Double Rainbow', 5, 7, 28, 'katy_perry.jfif', '2013-10-18', 'English', NULL, NULL, NULL, NOW()),
('By the Grace of God', 5, 7, 28, 'katy_perry.jfif', '2013-10-18', 'English', NULL, NULL, NULL, NOW()),

('Witness', 5, 7, 29, 'katy_perry.jfif', '2017-06-09', 'English', NULL, NULL, NULL, NOW()),
('Hey Hey Hey', 5, 7, 29, 'katy_perry.jfif', '2017-06-09', 'English', NULL, NULL, NULL, NOW()),
('Roulette', 5, 7, 29, 'katy_perry.jfif', '2017-06-09', 'English', NULL, NULL, NULL, NOW()),
('Swish Swish', 5, 7, 29, 'katy_perry.jfif', '2017-06-09', 'English', NULL, NULL, NULL, NOW()),
('Déjà Vu', 5, 7, 29, 'katy_perry.jfif', '2017-06-09', 'English', NULL, NULL, NULL, NOW()),
('Power', 5, 7, 29, 'katy_perry.jfif', '2017-06-09', 'English', NULL, NULL, NULL, NOW()),
('Mind Maze', 5, 7, 29, 'katy_perry.jfif', '2017-06-09', 'English', NULL, NULL, NULL, NOW()),
('Miss You More', 5, 7, 29, 'katy_perry.jfif', '2017-06-09', 'English', NULL, NULL, NULL, NOW()),
('Chained to the Rhythm', 5, 7, 29, 'katy_perry.jfif', '2017-06-09', 'English', NULL, NULL, NULL, NOW()),
('Tsunami', 5, 7, 29, 'katy_perry.jfif', '2017-06-09', 'English', NULL, NULL, NULL, NOW()),
('Bon Appétit', 5, 7, 29, 'katy_perry.jfif', '2017-06-09', 'English', NULL, NULL, NULL, NOW()),
('Bigger Than Me', 5, 7, 29, 'katy_perry.jfif', '2017-06-09', 'English', NULL, NULL, NULL, NOW()),
('Save as Draft', 5, 7, 29, 'katy_perry.jfif', '2017-06-09', 'English', NULL, NULL, NULL, NOW()),
('Pendulum', 5, 7, 29, 'katy_perry.jfif', '2017-06-09', 'English', NULL, NULL, NULL, NOW()),
('Into Me You See', 5, 7, 29, 'katy_perry.jfif', '2017-06-09', 'English', NULL, NULL, NULL, NOW()),

('Never Really Over', 5, 7, 30, 'katy_perry.jfif', '2020-08-28', 'English', NULL, NULL, NULL, NOW()),
('Cry About It Later', 5, 7, 30, 'katy_perry.jfif', '2020-08-28', 'English', NULL, NULL, NULL, NOW()),
('Teary Eyes', 5, 7, 30, 'katy_perry.jfif', '2020-08-28', 'English', NULL, NULL, NULL, NOW()),
('Daisies', 5, 7, 30, 'katy_perry.jfif', '2020-08-28', 'English', NULL, NULL, NULL, NOW()),
('Resilient', 5, 7, 30, 'katy_perry.jfif', '2020-08-28', 'English', NULL, NULL, NULL, NOW()),
('Not the End of the World', 5, 7, 30, 'katy_perry.jfif', '2020-08-28', 'English', NULL, NULL, NULL, NOW()),
('Smile', 5, 7, 30, 'katy_perry.jfif', '2020-08-28', 'English', NULL, NULL, NULL, NOW()),
('Champagne Problems', 5, 7, 30, 'katy_perry.jfif', '2020-08-28', 'English', NULL, NULL, NULL, NOW()),
('Tucked', 5, 7, 30, 'katy_perry.jfif', '2020-08-28', 'English', NULL, NULL, NULL, NOW()),
('Harleys in Hawaii', 5, 7, 30, 'katy_perry.jfif', '2020-08-28', 'English', NULL, NULL, NULL, NOW()),
('Only Love', 5, 7, 30, 'katy_perry.jfif', '2020-08-28', 'English', NULL, NULL, NULL, NOW()),
('What Makes a Woman', 5, 7, 30, 'katy_perry.jfif', '2020-08-28', 'English', NULL, NULL, NULL, NOW()),

('WOMAN''S WORLD', 5, 7, 31, 'katy_perry.jfif', '2024-09-20', 'English', NULL, NULL, NULL, NOW()),
('GIMME GIMME', 5, 7, 31, 'katy_perry.jfif', '2024-09-20', 'English', NULL, NULL, NULL, NOW()),
('GORGEOUS', 5, 7, 31, 'katy_perry.jfif', '2024-09-20', 'English', NULL, NULL, NULL, NOW()),
('I''M HIS, HE''S MINE', 5, 7, 31, 'katy_perry.jfif', '2024-09-20', 'English', NULL, NULL, NULL, NOW()),
('CRUSH', 5, 7, 31, 'katy_perry.jfif', '2024-09-20', 'English', NULL, NULL, NULL, NOW()),
('LIFETIMES', 5, 7, 31, 'katy_perry.jfif', '2024-09-20', 'English', NULL, NULL, NULL, NOW()),
('ALL THE LOVE', 5, 7, 31, 'katy_perry.jfif', '2024-09-20', 'English', NULL, NULL, NULL, NOW()),
('NIRVANA', 5, 7, 31, 'katy_perry.jfif', '2024-09-20', 'English', NULL, NULL, NULL, NOW()),
('ARTIFICIAL', 5, 7, 31, 'katy_perry.jfif', '2024-09-20', 'English', NULL, NULL, NULL, NOW()),
('TRUTH', 5, 7, 31, 'katy_perry.jfif', '2024-09-20', 'English', NULL, NULL, NULL, NOW()),
('WONDER', 5, 7, 31, 'katy_perry.jfif', '2024-09-20', 'English', NULL, NULL, NULL, NOW());

SET FOREIGN_KEY_CHECKS = 1;
