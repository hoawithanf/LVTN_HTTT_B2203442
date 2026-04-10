# RESTORE_CHECKLIST

Checklist khôi phục `NLN_PROJECT` trên máy khác mà không phụ thuộc vào XAMPP hiện tại.

## 1. Lấy source code

- clone repo:
  - `git clone https://github.com/hoawithanf/LVTN_HTTT_B2203442.git`
- vào thư mục project:
  - `cd LVTN_HTTT_B2203442`

## 2. Chuẩn bị môi trường PHP

Chọn một trong các phương án:

- `PHP built-in server` + MySQL/MariaDB
- `Laragon`
- `XAMPP`
- `Docker Compose`

Khuyến nghị nhanh nhất để phục hồi:

- cài PHP 8.x
- cài MySQL hoặc MariaDB
- dùng built-in server để chạy thử

## 3. Kiểm tra extension PHP

Đảm bảo các extension quan trọng đã bật:

- `mysqli`
- `curl`
- `json`
- `mbstring`
- `openssl`
- `fileinfo`

## 4. Tạo file `.env`

Tạo `.env` ở root project.

Biến có thể cần:

```env
OPENAI_API_KEY=
OPENAI_MODEL=gpt-4.1-mini
GEMINI_API_KEY=
GENIUS_TOKEN=
YOUTUBE_API_KEY=
```

Lưu ý:

- không commit `.env`
- không đẩy API key lên GitHub

## 5. Chuẩn bị database

Tạo database:

- `nln_lyrics`

Sau đó import dữ liệu từ máy cũ hoặc file SQL backup.

Nếu cần export từ máy cũ:

- dùng phpMyAdmin export
- hoặc dùng `mysqldump`

## 6. Rà cấu hình kết nối DB

Kiểm tra hai file:

- `config/database.php`
- `public/includes/database.php`

Xác nhận:

- host
- port
- username
- password
- database name
- charset `utf8mb4`

## 7. Chạy project không cần XAMPP

Nếu chỉ cần phục hồi nhanh để demo/dev:

```bash
php -S 127.0.0.1:8000 -t public
```

Sau đó mở:

- `http://127.0.0.1:8000`

## 8. Chạy bằng Docker Compose

Repo đã có sẵn:

- `Dockerfile`
- `docker-compose.yml`
- `docker/mysql/init/`

Các bước:

1. nếu có file SQL backup, đặt vào:
   - `docker/mysql/init/`
2. chạy:

```bash
docker compose up --build
```

Sau khi container lên:

- web:
  - `http://127.0.0.1:8080/public/`
- mysql từ máy host:
  - host `127.0.0.1`
  - port `3307`
  - db `nln_lyrics`
  - user `nln_user`
  - password `nln_password`

Nếu cần reset DB Docker từ đầu:

```bash
docker compose down -v
docker compose up --build
```

## 9. Kiểm tra các route quan trọng

Sau khi chạy, kiểm tra lần lượt:

- `public/index.php`
- `public/search.php`
- `public/post.php?id=...`
- `public/artist.php?id=...`
- `public/album.php?id=...`
- `public/profile.php`
- `public/recap.php`
- `public/persona.php`
- `admin/login.php`
- `admin/index.php`

## 10. Kiểm tra các chức năng trọng tâm

### Public

- đăng nhập / đăng ký
- search + search suggest
- trang bài hát:
  - lyrics
  - meaning
  - video YouTube
  - comments
  - related songs
  - add to playlist
- follow artist
- favorite album
- album rating
- profile recommendations
- recap
- persona

### Admin

- login admin
- dashboard
- danh sách user / artist / song / news
- add/edit/delete cơ bản

## 11. Kiểm tra AI/API ngoài

Nếu có dùng:

- OpenAI
- Gemini
- Genius
- YouTube

Hãy xác nhận:

- key đã có trong `.env`
- server có mạng
- request không bị firewall chặn

## 12. Các điểm cần nhớ khi tiếp tục phát triển

- route bài hát chính thức là `public/post.php`
- `public/song.php` chỉ là redirect compatibility
- `song_genres` không tồn tại trong DB live
- `songs.genre_id` là schema thật
- không commit `.env`
- không để AI bịa entity mới ngoài DB

## 13. Nếu phục hồi để viết báo cáo

Đọc theo thứ tự:

1. `README.md`
2. `AUDIT_PROJECT.md`
3. `REFACTOR_PRIORITY_PLAN.md`
4. `CHATGPT_REPORT_HANDOVER.md`

## 14. Nếu phục hồi để tiếp tục code

Ưu tiên đọc:

- `config/`
- `public/post.php`
- `public/profile.php`
- `public/includes/navbar.php`
- `config/recommendation_helpers.php`
- `config/persona_helpers.php`
- `config/playlist_helpers.php`
