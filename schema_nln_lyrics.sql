CREATE DATABASE IF NOT EXISTS nln_lyrics
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE nln_lyrics;

CREATE TABLE IF NOT EXISTS users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(150) NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'user',
  avatar VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY uniq_users_username (username),
  UNIQUE KEY uniq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS artists (
  artist_id INT AUTO_INCREMENT PRIMARY KEY,
  artist_name VARCHAR(150) NOT NULL,
  avatar VARCHAR(255) NULL,
  bio TEXT NULL,
  country VARCHAR(100) NULL,
  birth_year INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS genres (
  genre_id INT AUTO_INCREMENT PRIMARY KEY,
  genre_name VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS albums (
  album_id INT AUTO_INCREMENT PRIMARY KEY,
  album_name VARCHAR(150) NOT NULL,
  artist_id INT NOT NULL,
  cover_image VARCHAR(255) NULL,
  release_year INT NULL,
  release_date DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_albums_artist (artist_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS songs (
  song_id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  artist_id INT NOT NULL,
  genre_id INT NOT NULL,
  album_id INT NULL,
  cover_image VARCHAR(255) NULL,
  release_date DATE NULL,
  language VARCHAR(50) NULL,
  lyrics LONGTEXT NULL,
  meaning LONGTEXT NULL,
  youtube_video_id VARCHAR(64) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_songs_artist (artist_id),
  KEY idx_songs_genre (genre_id),
  KEY idx_songs_album (album_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS news (
  news_id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  image VARCHAR(255) NULL,
  summary TEXT NULL,
  content LONGTEXT NULL,
  artist_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_news_artist (artist_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
  notification_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  news_id INT NULL,
  artist_id INT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_notifications_user (user_id),
  KEY idx_notifications_news (news_id),
  KEY idx_notifications_artist (artist_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS search_logs (
  log_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  song_id INT NOT NULL,
  song_title VARCHAR(200) NOT NULL,
  artist_name VARCHAR(150) NOT NULL,
  cover_image VARCHAR(255) NULL,
  search_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_search_user (user_id),
  KEY idx_search_song (song_id),
  KEY idx_search_time (search_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS artist_follows (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  artist_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_artist_follow (user_id, artist_id),
  KEY idx_artist_follows_user (user_id),
  KEY idx_artist_follows_artist (artist_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS album_favorites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  album_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_album_fav (user_id, album_id),
  KEY idx_album_fav_user (user_id),
  KEY idx_album_fav_album (album_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_recommendations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  song_id INT NOT NULL,
  base_score DECIMAL(6,2) NOT NULL DEFAULT 0,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_user_rec (user_id, song_id),
  KEY idx_user_rec_user (user_id),
  KEY idx_user_rec_song (song_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comments (
  comment_id INT AUTO_INCREMENT PRIMARY KEY,
  song_id INT NOT NULL,
  user_id INT NOT NULL,
  parent_comment_id INT NULL DEFAULT NULL,
  content TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  KEY idx_comments_song (song_id),
  KEY idx_comments_user (user_id),
  KEY idx_comments_parent (parent_comment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comment_likes (
  like_id INT AUTO_INCREMENT PRIMARY KEY,
  comment_id INT NOT NULL,
  user_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_comment_like (comment_id, user_id),
  KEY idx_comment_likes_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS album_ratings (
  rating_id INT AUTO_INCREMENT PRIMARY KEY,
  album_id INT NOT NULL,
  user_id INT NOT NULL,
  rating TINYINT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY uniq_album_user (album_id, user_id),
  KEY idx_album (album_id),
  KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS playlists (
  playlist_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  playlist_name VARCHAR(150) NOT NULL,
  description TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_playlists_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS playlist_songs (
  playlist_song_id INT AUTO_INCREMENT PRIMARY KEY,
  playlist_id INT NOT NULL,
  song_id INT NOT NULL,
  added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_playlist_song (playlist_id, song_id),
  KEY idx_playlist_songs_playlist (playlist_id),
  KEY idx_playlist_songs_song (song_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS api_logs (
  log_id INT AUTO_INCREMENT PRIMARY KEY,
  endpoint VARCHAR(200) NULL,
  method VARCHAR(20) NULL,
  payload LONGTEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  user_id INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
