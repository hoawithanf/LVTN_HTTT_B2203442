# NLN_PROJECT

Tài liệu handover chính để ChatGPT/Codex hoặc người tiếp quản mới nắm nhanh trạng thái thực tế của project, cách chạy repo, cấu trúc lõi, và các quy tắc an toàn khi sửa code.

Repo GitHub hiện tại:

- `https://github.com/hoawithanf/LVTN_HTTT_B2203442`

## 1. Mục đích của README này

README này dùng để:

- chốt kiến trúc thật của project
- chốt schema database live đã xác minh
- chỉ ra route và file lõi cần đọc trước
- ghi lại các refactor lớn đã hoàn tất
- hướng dẫn clone/setup trên máy khác
- giảm rủi ro khi một agent mới tiếp quản để viết báo cáo hoặc tiếp tục phát triển

README này luôn phải bám theo source hiện có trong repo, không bám theo kế hoạch cũ nếu code đã thay đổi.

## 2. Tổng quan hệ thống

`NLN_PROJECT` là website âm nhạc viết bằng PHP thuần + MySQL/MariaDB.

Project có hai khu vực chính:

- `public/`: giao diện người dùng
- `admin/`: giao diện quản trị

Kiểu tổ chức source:

- render server-side
- include thủ công
- helper dùng chung trong `config/`
- các API nội bộ nhỏ trong `public/includes/`
- không dùng framework MVC hoàn chỉnh

## 3. Trạng thái database live đã chốt

Schema live được xác minh từ DB local `nln_lyrics`.

Các bảng hiện có:

- `album_favorites`
- `albums`
- `api_logs`
- `artist_follows`
- `artists`
- `comments`
- `genres`
- `news`
- `notifications`
- `search_logs`
- `songs`
- `user_recommendations`
- `users`

Các điểm rất quan trọng:

- `song_genres` không tồn tại trong DB live
- `songs.genre_id` là schema thật
- `songs.cover_image` và `albums.cover_image` cùng tồn tại
- `notifications` có các cột:
  - `notification_id`
  - `user_id`
  - `news_id`
  - `artist_id`
  - `is_read`
  - `created_at`

## 4. Route và file chính thức

### 4.1. Route bài hát

- route bài hát chính thức là `public/post.php?id=...`
- `public/song.php` chỉ còn là compatibility redirect

Mọi thay đổi logic chi tiết bài hát phải sửa ở:

- `public/post.php`

Không phát triển logic mới ở:

- `public/song.php`

### 4.2. Auth

Auth đã được chuẩn hóa theo `password_hash()` / `password_verify()` với backward compatibility cho hash cũ.

Core helper:

- `config/auth_helpers.php`

Các file auth đã được đồng bộ:

- `public/login.php`
- `public/signup.php`
- `public/includes/change_password.php`
- `admin/login.php`
- `admin/change_password.php`
- `admin/add_user.php`
- `admin/reset_admin_password.php`

## 5. Cấu trúc thư mục

```text
NLN_PROJECT/
|-- admin/
|-- config/
|-- public/
|-- recommendation/
|-- AUDIT_PROJECT.md
|-- CHATGPT_REPORT_HANDOVER.md
|-- PRODUCT_PLAN_PUBLIC_EXPANSION.md
|-- README.md
|-- REFACTOR_PRIORITY_PLAN.md
|-- SPEC_PERSONA_PAGE.md
|-- SPEC_RECAP_PAGE.md
```

## 6. Các file lõi cần đọc trước

### 6.1. `config/`

Đây là nơi chứa helper lõi và là vùng quan trọng nhất khi tiếp quản.

- `config/database.php`
  - kết nối DB phía admin/dùng chung
- `config/auth_helpers.php`
  - verify/migrate password
- `config/song_helpers.php`
  - resolve cover bài hát theo ưu tiên `songs.cover_image -> albums.cover_image -> fallback`
- `config/upload_helpers.php`
  - validate upload ảnh theo extension/MIME/size/base64 cropper
- `config/comment_helpers.php`
  - helper comment tree, like, edit, delete, reply
- `config/recommendation_helpers.php`
  - recommendation ở profile, cache + refresh + fallback
- `config/album_rating_helpers.php`
  - lưu và tổng hợp rating album
- `config/openai_helpers.php`
  - helper gọi OpenAI
- `config/youtube_helpers.php`
  - resolve `youtube_video_id` hợp lệ và embeddable
- `config/recap_helpers.php`
  - dữ liệu Monthly Recap
- `config/persona_helpers.php`
  - AI Music Persona, local-first + OpenAI copy layer
- `config/related_song_helpers.php`
  - related songs cho `post.php`
- `config/playlist_helpers.php`
  - schema/helper playlist cá nhân

### 6.2. `public/`

Entry points chính:

- `public/index.php`
- `public/search.php`
- `public/post.php`
- `public/artist.php`
- `public/album.php`
- `public/profile.php`
- `public/news_list.php`
- `public/news.php`
- `public/charts.php`
- `public/login.php`
- `public/signup.php`
- `public/recap.php`
- `public/persona.php`

Các file include quan trọng:

- `public/includes/database.php`
- `public/includes/session.php`
- `public/includes/header.php`
- `public/includes/navbar.php`
- `public/includes/footer.php`

Các API nội bộ quan trọng:

- `public/includes/api_search_suggest.php`
- `public/includes/api_follow_artist.php`
- `public/includes/api_favorite_album.php`
- `public/includes/api_rate_album.php`
- `public/includes/api_get_comments.php`
- `public/includes/api_add_comment.php`
- `public/includes/api_edit_comment.php`
- `public/includes/api_delete_comment.php`
- `public/includes/api_like_comment.php`
- `public/includes/api_mark_notification_read.php`
- `public/includes/api_album_songs.php`
- `public/includes/api_add_to_playlist.php`
- `public/includes/ajax_fetch_lyrics.php`
- `public/includes/ajax_analyze_lyrics.php`

### 6.3. `admin/`

Entry points chính:

- `admin/index.php`
- `admin/login.php`
- `admin/profile.php`
- `admin/users.php`
- `admin/artists.php`
- `admin/songs.php`
- `admin/news.php`
- `admin/search_logs.php`
- `admin/song_details.php`
- `admin/song_trend.php`
- `admin/export_report.php`

Guard admin:

- `admin/includes/admin_auth.php`

## 7. Các refactor lớn đã hoàn tất

### 7.1. Pha 0 đến Pha 7

Các pha ưu tiên cao trong `REFACTOR_PRIORITY_PLAN.md` đã hoàn tất phần lõi:

- xác minh schema DB live
- hợp nhất auth/password
- chuẩn hóa notifications theo schema thật
- đóng băng `public/song.php` thành redirect compatibility
- loại phụ thuộc vào `song_genres`
- chuẩn hóa cover bài hát
- chuẩn hóa upload validation
- giảm rủi ro XSS ở các luồng public quan trọng
- chuyển nhiều query nội suy sang prepared statement

### 7.2. Những tính năng public đã mở rộng thêm

Đã có các nhóm tính năng sau:

- `post.php`
  - lyrics
  - meaning bằng AI
  - YouTube video
  - comments thread/reply/edit/delete/like
  - related songs
  - thêm bài hát vào playlist
- `artist.php`
  - follow artist không reload
  - album preview popup
- `album.php`
  - favorite album không reload
  - album rating tương tác
- `profile.php`
  - lịch sử nghe/tìm kiếm
  - follows
  - favorites
  - recommendation
  - playlists
- `recap.php`
  - Monthly Recap
- `persona.php`
  - AI Music Persona local-first + OpenAI wording layer

## 8. Quy tắc làm việc khi tiếp quản

### 8.1. Nguyên tắc kỹ thuật

- không giả định tồn tại `song_genres`
- không khôi phục logic cũ dựa vào `public/song.php`
- luôn ưu tiên prepared statements cho query có input
- không đổi route public/admin nếu chưa xác định rõ toàn bộ luồng
- không sửa schema theo trí nhớ, phải bám schema live thật
- khi thêm tính năng AI:
  - AI chỉ nên phân loại, diễn giải, xếp hạng hoặc viết mô tả
  - không để AI bịa bài hát, nghệ sĩ, album không tồn tại trong DB

### 8.2. Nguyên tắc repo

- `.env` không được commit
- `api_key.txt` không được commit
- repo hiện đã có `.gitignore` cho các file bí mật này

## 9. Clone và setup trên máy khác

### 9.1. Clone repo

```bash
git clone https://github.com/hoawithanf/LVTN_HTTT_B2203442.git
cd LVTN_HTTT_B2203442
```

### 9.2. Chuẩn bị file môi trường

Tạo file `.env` ở root project và điền các biến cần thiết theo nhu cầu:

```env
OPENAI_API_KEY=...
OPENAI_MODEL=gpt-4.1-mini
GEMINI_API_KEY=...
GENIUS_TOKEN=...
YOUTUBE_API_KEY=...
```

Lưu ý:

- không commit `.env`
- không hardcode API key trong PHP

### 9.3. Chuẩn bị database

Tạo database:

- tên DB đề xuất: `nln_lyrics`

Sau đó import dữ liệu/bản dump nếu có.

Nếu chưa có dump:

- cần sao chép database hiện tại từ máy cũ
- hoặc export SQL từ phpMyAdmin / `mysqldump`

### 9.4. Cách chạy không phụ thuộc XAMPP

Project không bắt buộc phải chạy bằng XAMPP. Có thể phục hồi theo các cách sau:

- PHP built-in server + MySQL/MariaDB local
- Laragon
- Docker compose tự viết

Cách nhanh nhất nếu máy đã có PHP:

```bash
php -S 127.0.0.1:8000 -t public
```

Lưu ý:

- cách này chỉ phù hợp để demo/dev nhanh
- cần sửa cấu hình DB host/user/password trong file kết nối nếu môi trường mới khác máy cũ

### 9.5. Các file cấu hình DB cần kiểm tra

- `public/includes/database.php`
- `config/database.php`

Trên máy mới, cần rà:

- host
- port
- username
- password
- database name
- charset `utf8mb4`

## 10. Checklist khôi phục project

Checklist chi tiết nằm ở:

- `RESTORE_CHECKLIST.md`

Tài liệu này dành riêng cho việc:

- clone repo về máy mới
- cấu hình lại môi trường
- chuẩn bị DB
- xác minh các route chính
- tiếp tục phát triển mà không cần XAMPP hiện tại

## 11. Tài liệu bổ sung cần đọc

- `AUDIT_PROJECT.md`
- `REFACTOR_PRIORITY_PLAN.md`
- `PRODUCT_PLAN_PUBLIC_EXPANSION.md`
- `SPEC_RECAP_PAGE.md`
- `SPEC_PERSONA_PAGE.md`
- `CHATGPT_REPORT_HANDOVER.md`

## 12. Nếu tiếp quản bằng ChatGPT/Codex

Đọc theo thứ tự:

1. `README.md`
2. `AUDIT_PROJECT.md`
3. `REFACTOR_PRIORITY_PLAN.md`
4. `CHATGPT_REPORT_HANDOVER.md`
5. các file trong `config/`
6. route trọng tâm cần sửa, thường là:
   - `public/post.php`
   - `public/profile.php`
   - `public/search.php`
   - `public/includes/navbar.php`

Nếu đang tiếp tục phát triển tính năng mới, nên xác nhận trước:

- tính năng đó có đụng schema DB không
- có đụng `post.php` hoặc comment system không
- có dùng AI để sinh dữ liệu mới ngoài DB hay không

Nếu câu trả lời là có, phải kiểm tra kỹ hơn trước khi sửa.
