# NLN_PROJECT

Tài liệu handover để ChatGPT/Codex tiếp quản project nhanh, đúng hướng và không sửa lệch core system.

README này bám theo trạng thái source hiện tại trong workspace, không chỉ theo kế hoạch cũ.

## 1. Mục tiêu của tài liệu này

Khi một agent mới tiếp quản project, cần hiểu ngay:

- project đang chạy theo kiến trúc nào
- route nào là route chính thức
- schema database live hiện là gì
- file nào đang là core file thật sự cần đọc trước
- những refactor lớn nào đã hoàn thành
- những vùng nào còn dễ gãy nếu sửa thiếu ngữ cảnh

README này đóng vai trò:

- handover note
- bản đồ kiến trúc hiện tại
- checklist an toàn trước khi sửa code

## 2. Tổng quan project

`NLN_PROJECT` là website PHP thuần + MySQL chạy local trên XAMPP.

Project có 2 khu vực chính:

- `public/`: giao diện người dùng
- `admin/`: giao diện quản trị

Đây không phải project MVC/framework hoàn chỉnh. Source theo kiểu:

- include thủ công
- render server-side
- API nội bộ nhỏ nằm trong `public/includes/`
- helper dùng chung nằm trong `config/`

## 3. Trạng thái hiện tại đã chốt

### 3.1. Database live

Schema live đã được xác minh trực tiếp từ MySQL local `nln_lyrics`.

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

Các điểm cực kỳ quan trọng:

- `song_genres` không tồn tại trong DB live
- `songs.genre_id` là schema thật
- `songs.cover_image` và `albums.cover_image` cùng tồn tại
- `notifications` đang có các cột:
  - `notification_id`
  - `user_id`
  - `news_id`
  - `artist_id`
  - `is_read`
  - `created_at`

### 3.2. Route bài hát chính thức

- route bài hát chính thức là `public/post.php?id=...`
- `public/song.php` chỉ còn là compatibility redirect

Khi sửa logic chi tiết bài hát, luôn sửa ở:

- `public/post.php`

Không phát triển logic mới ở:

- `public/song.php`

### 3.3. Chuẩn password hiện tại

Hệ thống auth đã được chuẩn hóa theo `password_hash()` / `password_verify()` với backward compatibility cho hash cũ.

Core helper:

- `config/auth_helpers.php`

Các luồng đã được cập nhật:

- `public/login.php`
- `public/signup.php`
- `public/includes/change_password.php`
- `admin/login.php`
- `admin/change_password.php`
- `admin/add_user.php`
- `admin/reset_admin_password.php`

Ý nghĩa:

- user/admin cũ vẫn đăng nhập được
- lần đăng nhập thành công có thể migrate hash cũ sang hash mới
- user tạo từ admin không còn lệch chuẩn với public login

## 4. Cấu trúc thư mục

```text
NLN_PROJECT/
|-- admin/
|-- config/
|-- public/
|-- AUDIT_PROJECT.md
|-- REFACTOR_PRIORITY_PLAN.md
|-- README.md
```

## 5. Các file và thư mục quan trọng

## 5.1. `config/`

Đây là nơi chứa helper và cấu hình dùng chung thật sự quan trọng.

### File lõi

- `config/database.php`
  - kết nối DB cho admin/dùng chung
- `config/auth_helpers.php`
  - verify/migrate password
- `config/song_helpers.php`
  - resolve cover bài hát theo ưu tiên `songs.cover_image` -> `albums.cover_image` -> fallback
- `config/upload_helpers.php`
  - validate upload ảnh theo extension, MIME, size, base64 cropper
- `config/comment_helpers.php`
  - schema helper + tree comment + delete comment
- `config/recommendation_helpers.php`
  - recommendation cho profile, có cache + refresh + fallback
- `config/album_rating_helpers.php`
  - rating album, summary, save rating
- `config/openai_helpers.php`
  - helper gọi OpenAI Responses API
- `config/youtube_helpers.php`
  - resolve `youtube_video_id` hợp lệ, embeddable

## 5.2. `public/`

### Entry points chính

- `public/index.php`
  - homepage
- `public/search.php`
  - search bài hát
- `public/post.php`
  - trang chi tiết bài hát, lyrics, meaning, comments, YouTube
- `public/artist.php`
  - hồ sơ nghệ sĩ
- `public/album.php`
  - album detail, favorite, album rating
- `public/profile.php`
  - hồ sơ user, history, follows, favorites, AI recommendations
- `public/news_list.php`
  - danh sách tin tức
- `public/news.php`
  - chi tiết tin tức
- `public/charts.php`
  - bảng xếp hạng public
- `public/login.php`
  - đăng nhập
- `public/signup.php`
  - đăng ký
- `public/logout.php`
  - logout

### File dùng chung quan trọng

- `public/includes/database.php`
- `public/includes/session.php`
- `public/includes/header.php`
- `public/includes/navbar.php`
- `public/includes/footer.php`

### API nội bộ quan trọng

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
- `public/includes/ajax_fetch_lyrics.php`
- `public/includes/ajax_analyze_lyrics.php`

## 5.3. `admin/`

### Entry points chính

- `admin/index.php`
- `admin/login.php`
- `admin/logout.php`
- `admin/profile.php`
- `admin/change_password.php`
- `admin/users.php`
- `admin/artists.php`
- `admin/songs.php`
- `admin/news.php`
- `admin/search_logs.php`
- `admin/song_details.php`
- `admin/song_trend.php`
- `admin/top_songs.php`
- `admin/top_artists.php`
- `admin/export_report.php`

### CRUD liên quan

- `admin/add_user.php`
- `admin/add_artist.php`
- `admin/add_song.php`
- `admin/add_news.php`
- `admin/edit_song.php`
- `admin/edit_news.php`
- `admin/delete_user.php`
- `admin/delete_artist.php`
- `admin/delete_song.php`
- `admin/delete_news.php`

### File dùng chung

- `admin/includes/admin_auth.php`
- `admin/includes/header.php`
- `admin/includes/sidebar.php`
- `admin/includes/topbar.php`
- `admin/includes/footer.php`
- `admin/includes/scripts.php`

## 6. Những refactor lớn đã hoàn tất

## 6.1. Pha 0 đến Pha 7

Các pha ưu tiên cao trong `REFACTOR_PRIORITY_PLAN.md` đã hoàn thành phần lõi.

### Pha 0

- xác minh schema DB live
- chốt route bài hát thật sự là `post.php`

### Pha 1

- hợp nhất auth/password
- thêm `config/auth_helpers.php`

### Pha 2

- thống nhất notifications theo schema live
- bỏ insert notification sai schema ở luồng add song
- navbar public và admin đã bám đúng `notifications`

### Pha 3

- đóng băng `public/song.php`
- chuyển về redirect an toàn sang `post.php`

### Pha 4

- chốt logic `genre` theo `songs.genre_id`
- bỏ phụ thuộc `song_genres`
- thống nhất resolve cover bài hát bằng helper

### Pha 5

- thêm `config/upload_helpers.php`
- chuẩn hóa validate upload ảnh ở các luồng admin chính

### Pha 6

- giảm rủi ro XSS ở `news`, comments API, album songs API

### Pha 7

- dọn thêm prepared statement
- thêm guard admin cho destructive routes

## 6.2. Các thay đổi nghiệp vụ và UI sau 7 pha

Ngoài 7 pha lõi, project hiện còn có các thay đổi thực tế sau:

### Post page

`public/post.php` đã được mở rộng:

- lyrics fetch theo DB/cache + fallback ngoài
- meaning re-analyze bằng AI qua `ajax_analyze_lyrics.php`
- resolve video YouTube an toàn hơn qua `config/youtube_helpers.php`
- comments hỗ trợ:
  - reply
  - edit
  - like
  - delete
- quick info đã được đưa về đúng vị trí dưới banner

### Profile recommendations

`public/profile.php` đã được nâng cấp:

- recommendation dùng `config/recommendation_helpers.php`
- hỗ trợ refresh recommendations
- có fallback mạnh để không trả rỗng vô lý
- cache vào `user_recommendations`

### Album page

`public/album.php` hiện có:

- favorite album qua AJAX
- icon favorite phản hồi tại chỗ, không cần reload
- album rating thật:
  - average rating
  - rating count
  - interactive stars
- backend:
  - `config/album_rating_helpers.php`
  - `public/includes/api_rate_album.php`

### Artist page

`public/artist.php` hiện có:

- layout hero + top tracks + album preview modal + info panel
- follow artist qua AJAX, không reload trang
- album preview modal đã được dọn lại về 1 bản sạch

### Navbar/header public

`public/includes/navbar.php` hiện đã được custom lại:

- notification dropdown gọn hơn
- profile dropdown gọn hơn
- custom search dropdown
- focus search sẽ hiện recent searches
- khi gõ sẽ gọi `api_search_suggest.php`

Lưu ý:

- console PowerShell của agent có thể vẫn hiển thị mojibake
- nhưng file source đang được ghi mới lại theo UTF-8 ở các vùng đã sửa

### Auth UI

Hai trang auth public đã được viết lại giao diện:

- `public/login.php`
- `public/signup.php`

Mục tiêu:

- đồng bộ design language với public pages
- giữ nguyên flow auth hiện có

## 7. Core contract của các tính năng quan trọng

## 7.1. Search

- search detail page: `public/search.php`
- navbar search suggest: `public/includes/navbar.php`
- API suggest: `public/includes/api_search_suggest.php`

Nguồn dữ liệu chính:

- `songs`
- `artists`
- `albums`
- `genres`
- `search_logs`

## 7.2. Comments

Comment system hiện không còn là comment phẳng đơn giản.

Schema helper tự đảm bảo thêm:

- `comments.parent_comment_id`
- `comments.updated_at`
- `comment_likes`

Core:

- `config/comment_helpers.php`
- `public/includes/api_get_comments.php`
- `public/includes/api_add_comment.php`
- `public/includes/api_edit_comment.php`
- `public/includes/api_delete_comment.php`
- `public/includes/api_like_comment.php`

## 7.3. Recommendation

Core:

- `config/recommendation_helpers.php`
- `public/profile.php`

Luồng:

- lấy behavior từ DB
- build candidate pool
- xếp hạng/cached recommendations
- fallback global nếu user data còn mỏng

## 7.4. Meaning và OpenAI

Core:

- `config/openai_helpers.php`
- `public/includes/ajax_analyze_lyrics.php`
- `public/includes/meaning_api_openai.php`

Lưu ý:

- phần OpenAI phụ thuộc `OPENAI_API_KEY` trong `.env`
- không giả định ChatGPT web và API key là cùng một tài khoản

## 7.5. YouTube video

Core:

- `config/youtube_helpers.php`
- `public/post.php`

Mục tiêu:

- chỉ dùng video embeddable/public
- ưu tiên official/lyric/audio
- loại bớt video nhiễu

## 8. Quy tắc làm việc khi sửa project này

Đây là quy tắc thực tế nên giữ nguyên cho mọi lần tiếp quản:

- luôn đọc file entry point trước khi sửa
- nếu sửa logic bài hát, bắt đầu từ `public/post.php`
- không dùng `public/song.php` để phát triển thêm logic
- không giả định có bảng `song_genres`
- luôn coi `songs.genre_id` là schema thật
- khi sửa notifications, bám đúng schema live hiện tại
- ưu tiên `prepared statement`
- ưu tiên helper đang có trong `config/` thay vì viết logic trùng
- không đổi route hiện có nếu chưa có lý do rất rõ
- không phá luồng admin/public đang chạy ổn để đổi UI đơn thuần
- nếu sửa upload, dùng `config/upload_helpers.php`
- nếu sửa password/auth, dùng `config/auth_helpers.php`
- nếu sửa comments, đọc `config/comment_helpers.php` trước
- nếu sửa recommendation, đọc `config/recommendation_helpers.php` trước

## 9. Những điểm dễ gãy cần cảnh giác

- một số file cũ vẫn còn literal mojibake trong source chưa được dọn hết
- console của agent có thể hiển thị tiếng Việt sai, không đồng nghĩa source file đang sai
- `public/includes/navbar.php` và `public/search.php` cùng liên quan search suggest, dễ sửa lệch nếu không đối chiếu cả hai
- `public/post.php` đang là file lớn, chứa nhiều JS inline và nhiều vùng logic
- `public/album.php`, `public/artist.php`, `public/profile.php` đều đã được nâng nhiều feature, không còn là page tĩnh đơn giản
- `admin/` vẫn còn nền SB Admin 2 cũ ở vài chỗ, không nên giả định toàn bộ admin đã đồng bộ hoàn toàn
- `search_logs` là bảng nền cho rất nhiều chức năng:
  - homepage
  - charts
  - profile history
  - recommendation
  - admin analytics

## 10. Thứ tự đọc source nếu cần tiếp quản nhanh

Đây là thứ tự nên đọc nếu một agent mới vào dự án:

1. `README.md`
2. `AUDIT_PROJECT.md`
3. `REFACTOR_PRIORITY_PLAN.md`
4. `config/database.php`
5. `public/includes/database.php`
6. `config/auth_helpers.php`
7. `config/song_helpers.php`
8. `config/comment_helpers.php`
9. `config/recommendation_helpers.php`
10. `public/includes/navbar.php`
11. `public/index.php`
12. `public/search.php`
13. `public/post.php`
14. `public/profile.php`
15. `public/artist.php`
16. `public/album.php`
17. `public/login.php`
18. `public/signup.php`
19. `admin/index.php`
20. `admin/users.php`, `admin/artists.php`, `admin/songs.php`, `admin/news.php`

## 11. Checklist trước khi sửa code

- xác định file route thật đang được dùng
- đối chiếu include chain của file
- xác định đang ở `public` hay `admin`
- xác định session hiện dùng là user hay admin
- xác định tính năng đã có helper chung chưa
- nếu đụng DB, kiểm tra schema live chứ không chỉ nhìn source cũ
- nếu đụng UI, tránh kéo theo refactor logic ngoài phạm vi
- nếu thấy chuỗi tiếng Việt méo trong terminal, kiểm tra lại chính file source trước khi kết luận lỗi mojibake

## 12. Môi trường và config

### Database hiện tại

- host: `localhost`
- user: `root`
- password: rỗng
- database: `nln_lyrics`

### `.env`

Project có dùng `.env` cho một số integration.

Biến đã/ có thể được dùng:

- `OPENAI_API_KEY`
- `OPENAI_MODEL`
- `GEMINI_API_KEY`
- `GENIUS_TOKEN`
- `YOUTUBE_API_KEY`

Lưu ý:

- OpenAI API và ChatGPT browser là hai lớp khác nhau
- API key chỉ có tác dụng nếu tài khoản/project chứa key có billing/quota hợp lệ

## 13. Tài liệu liên quan

- `AUDIT_PROJECT.md`
  - audit chi tiết theo file
- `REFACTOR_PRIORITY_PLAN.md`
  - kế hoạch refactor ưu tiên cao

## 14. Kết luận handover

Tại thời điểm README này được cập nhật:

- core refactor nền đã hoàn thành phần quan trọng
- route bài hát đã chốt về `public/post.php`
- auth đã được chuẩn hóa
- notifications đã được chốt theo schema live
- comment/recommendation/album rating/favorite/follow đã không còn là feature tĩnh đơn giản
- navbar/search/auth UI đã được nâng theo design language mới của public side

Nếu một agent mới tiếp quản, không nên bắt đầu bằng việc “làm đẹp toàn hệ thống”.
Thứ tự đúng là:

1. đọc README này
2. đọc các helper trong `config/`
3. xác minh route và DB live
4. chỉ sau đó mới sửa feature cụ thể
