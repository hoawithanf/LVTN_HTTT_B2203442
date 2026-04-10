# CHATGPT_REPORT_HANDOVER

## Mục đích file này

File này dùng để **prompt cho ChatGPT** nhằm giúp ChatGPT nhanh chóng nắm được toàn bộ project `NLN_PROJECT` và hỗ trợ viết báo cáo đồ án.

Khác với [README.md](C:/xampp/htdocs/NLN_PROJECT/README.md), file này được viết theo hướng:

- ngắn gọn hơn
- tập trung vào góc nhìn báo cáo
- nêu rõ hệ thống đang làm gì
- chỉ ra công nghệ, kiến trúc, dữ liệu, tính năng, AI, và các quyết định kỹ thuật quan trọng

---

## Prompt gợi ý để dán cho ChatGPT

```text
Tôi đang làm báo cáo cho project web âm nhạc tên là NLN_PROJECT. 
Hãy đọc kỹ phần mô tả hệ thống dưới đây và hỗ trợ tôi viết báo cáo theo đúng thực trạng project, không bịa thêm tính năng ngoài nội dung được cung cấp.

Yêu cầu rất quan trọng:
- Chỉ bám theo thông tin tôi cung cấp dưới đây
- Không giả định schema khác với schema live
- Không dùng các chi tiết mâu thuẫn với phần mô tả này
- Nếu cần viết báo cáo, hãy ưu tiên giọng văn học thuật, rõ ràng, dễ bảo vệ trước giáo viên
- Nếu cần phân tích hệ thống, hãy phân biệt rõ: tính năng đã hoàn thành, tính năng đang mở rộng, và hướng phát triển tiếp theo

====================
TÓM TẮT PROJECT
====================

Tên project: NLN_PROJECT

Loại hệ thống:
- Website âm nhạc/lyrics
- Có khu vực public cho người dùng
- Có khu vực admin để quản trị dữ liệu

Mục tiêu chính:
- Quản lý bài hát, nghệ sĩ, album, thể loại, tin tức
- Hiển thị lời bài hát, ý nghĩa bài hát
- Cho phép người dùng tương tác: bình luận, theo dõi nghệ sĩ, yêu thích album
- Gợi ý nội dung âm nhạc theo hành vi người dùng
- Ứng dụng AI vào một số chức năng như phân tích meaning và music persona

Stack công nghệ:
- PHP thuần
- MySQL / MariaDB
- HTML, CSS, Bootstrap, JavaScript
- XAMPP local
- Một số helper AI / YouTube / recommendation viết trong PHP

Kiến trúc thư mục chính:
- `public/`: giao diện người dùng
- `admin/`: giao diện quản trị
- `config/`: helper và logic dùng chung
- `public/includes/`: các API endpoint / include file / DB / session

====================
SCHEMA DATABASE LIVE
====================

Database live hiện tại: `nln_lyrics`

Các bảng đã xác minh đang tồn tại:
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

Lưu ý schema rất quan trọng:
- Bảng `song_genres` KHÔNG tồn tại trong DB live
- `songs.genre_id` là schema thật đang dùng
- `notifications` có các cột:
  - `notification_id`
  - `user_id`
  - `news_id`
  - `artist_id`
  - `is_read`
  - `created_at`

====================
ROUTE / FILE QUAN TRỌNG
====================

Public:
- `public/index.php`: trang chủ
- `public/search.php`: tìm kiếm bài hát / nghệ sĩ
- `public/post.php`: trang chi tiết bài hát, lyrics, meaning, comment, video
- `public/artist.php`: trang nghệ sĩ
- `public/album.php`: trang album
- `public/charts.php`: bảng xếp hạng
- `public/news_list.php`: danh sách tin tức
- `public/news.php`: chi tiết tin tức
- `public/profile.php`: hồ sơ người dùng, lịch sử, recommendation
- `public/recap.php`: tổng hợp recap theo tháng
- `public/persona.php`: music persona của người dùng
- `public/login.php`, `public/signup.php`: xác thực người dùng

Admin:
- `admin/index.php`: dashboard admin
- các trang CRUD liên quan đến users, artists, songs, news

Config / helper:
- `config/auth_helpers.php`
- `config/song_helpers.php`
- `config/upload_helpers.php`
- `config/openai_helpers.php`
- `config/recommendation_helpers.php`
- `config/comment_helpers.php`
- `config/youtube_helpers.php`
- `config/recap_helpers.php`
- `config/persona_helpers.php`

Lưu ý route:
- Route bài hát chính thức là `public/post.php`
- `public/song.php` chỉ còn là compatibility redirect, không còn là route chính

====================
CHỨC NĂNG CHÍNH ĐÃ CÓ
====================

1. Quản lý nội dung âm nhạc
- Admin có thể quản lý bài hát, nghệ sĩ, người dùng, tin tức
- Public có thể xem bài hát, nghệ sĩ, album, charts, news

2. Tìm kiếm bài hát
- Có trang search riêng
- Có gợi ý tìm kiếm ở navbar
- Có recent searches

3. Trang chi tiết bài hát (`post.php`)
- Hiển thị lyrics
- Có phần meaning bài hát
- Có video YouTube bài hát
- Có comment
- Có thể reply comment, like comment, sửa comment của chính mình

4. Tương tác người dùng
- Follow nghệ sĩ
- Favorite album
- Rating album
- Comment bài hát

5. Notification
- Notification đã được refactor để bám đúng schema live
- User xem notification từ bảng `notifications`

6. Recommendation
- `profile.php` có block gợi ý bài hát cá nhân hóa
- Dữ liệu đầu vào lấy từ:
  - `search_logs`
  - `artist_follows`
  - `album_favorites`
  - `user_recommendations`
- Recommendation có fallback local nếu AI không hoạt động

7. Recap
- Có trang `recap.php`
- Tổng hợp theo tháng
- Hiển thị:
  - tổng lượt quan tâm
  - số bài hát khác nhau
  - số nghệ sĩ khác nhau
  - số album liên quan
  - ngày hoạt động
  - top songs
  - top artists
  - top album
  - top genre
  - xu hướng khám phá vs nghe lại

8. Music Persona
- Có trang `persona.php`
- Local-first classification
- Dùng dữ liệu thật để phân loại persona âm nhạc của user
- OpenAI chỉ dùng để viết lại mô tả/insight tự nhiên hơn nếu có key và hoạt động
- Nếu OpenAI lỗi thì fallback về local description

====================
AI TRONG PROJECT
====================

AI được dùng theo hướng hỗ trợ, không để AI tự bịa dữ liệu.

1. Meaning bài hát
- Có thể phân tích ý nghĩa bài hát bằng AI
- Kết quả có thể lưu lại vào DB

2. Recommendation
- Recommendation ưu tiên candidate pool từ DB trước
- AI chỉ nên xếp hạng / diễn giải trên candidate có sẵn

3. Music Persona
- Local classifier quyết định persona_key / persona_title
- OpenAI chỉ viết mô tả tự nhiên hơn
- Không cho AI tự bịa nghệ sĩ / album / bài hát ngoài DB

Nguyên tắc AI quan trọng:
- AI không phải nguồn sự thật
- DB và heuristic local mới là lõi
- AI chỉ dùng cho:
  - diễn giải
  - mô tả
  - xếp hạng nhẹ
  - làm giao diện sản phẩm tự nhiên hơn

====================
NHỮNG REFACTOR / CẢI TIẾN QUAN TRỌNG ĐÃ LÀM
====================

1. Chuẩn hóa auth/password
- Hợp nhất xử lý password theo helper dùng chung
- Có backward compatibility cho dữ liệu hash cũ

2. Chuẩn hóa notifications
- Refactor theo đúng schema live

3. Loại bỏ phụ thuộc legacy
- `public/song.php` không còn là route bài hát chính

4. Chuẩn hóa genre và cover image
- Không dùng `song_genres`
- Dùng `songs.genre_id`
- Có helper resolve cover từ song/album

5. Chuẩn hóa upload
- Validate MIME/type/size tốt hơn

6. Giảm rủi ro XSS
- Escape output ở nhiều luồng public/API quan trọng

7. Prepared statements / cleanup query
- Chuyển nhiều query quan trọng sang prepared statements

8. UTF-8 cleanup
- Đã có nhiều đợt dọn mojibake ở public/admin
- Tuy nhiên vẫn có thể còn dữ liệu cũ bị lưu sai encoding trong database

====================
GIAO DIỆN / UX ĐÃ CẢI TIẾN
====================

1. Admin
- Dashboard và các trang admin chính đã được đồng bộ UI theo ngôn ngữ thiết kế hiện đại hơn

2. Public
- `search.php` được tối ưu giao diện và trải nghiệm tìm kiếm
- `post.php` đã cải tiến hero, chips, summary, comment interaction
- `artist.php`, `album.php`, `profile.php`, `navbar` đã được tối ưu nhiều phần
- `recap.php` và `persona.php` là các page mới theo hướng modern UI

====================
ĐIỂM CẦN LƯU Ý KHI VIẾT BÁO CÁO
====================

1. Đây là website âm nhạc dùng dữ liệu hành vi kiểu:
- tìm kiếm
- xem bài hát
- tương tác

Chứ không phải hệ thống streaming hoàn chỉnh có playback log như Spotify thật.

2. Vì vậy khi viết báo cáo nên dùng từ:
- quan tâm
- tìm kiếm
- khám phá
- tương tác

Tránh viết quá đà kiểu:
- phát nhạc
- streaming minutes
- nghe 24/7

3. Các tính năng AI nên mô tả trung thực:
- AI hỗ trợ phân tích, mô tả và cá nhân hóa
- không phải AI sinh toàn bộ hệ thống recommendation từ hư vô

4. Project đã có định hướng mở rộng tiếp:
- Related Songs dưới `post.php`
- Playlist cá nhân kiểu Spotify/Apple Music
- Discovery Hub
- các hướng mở rộng public khác

====================
HƯỚNG VIẾT BÁO CÁO ĐỀ XUẤT
====================

Khi hỗ trợ tôi viết báo cáo, hãy ưu tiên các mục:
- Giới thiệu đề tài
- Mục tiêu hệ thống
- Phân tích yêu cầu
- Thiết kế cơ sở dữ liệu
- Thiết kế chức năng
- Thiết kế giao diện
- Cài đặt và triển khai
- Ứng dụng AI trong hệ thống
- Kết quả đạt được
- Hạn chế
- Hướng phát triển

Nếu tôi yêu cầu viết từng phần, hãy viết bám thật sát dữ liệu/tính năng ở trên.
```

---

## Khi dùng file này

Khuyến nghị cách làm:

1. Dán toàn bộ khối prompt ở trên cho ChatGPT
2. Sau đó yêu cầu ChatGPT viết từng chương hoặc từng mục báo cáo
3. Nếu cần, cho ChatGPT tham chiếu thêm:
   - [README.md](C:/xampp/htdocs/NLN_PROJECT/README.md)
   - [AUDIT_PROJECT.md](C:/xampp/htdocs/NLN_PROJECT/AUDIT_PROJECT.md)
   - [REFACTOR_PRIORITY_PLAN.md](C:/xampp/htdocs/NLN_PROJECT/REFACTOR_PRIORITY_PLAN.md)
   - [PRODUCT_PLAN_PUBLIC_EXPANSION.md](C:/xampp/htdocs/NLN_PROJECT/PRODUCT_PLAN_PUBLIC_EXPANSION.md)

---

## Gợi ý prompt nối tiếp

Sau khi ChatGPT đã đọc prompt trên, có thể dùng tiếp các câu như:

- `Hãy viết phần Giới thiệu đề tài và Mục tiêu hệ thống cho báo cáo tốt nghiệp, giọng văn học thuật, dài khoảng 2-3 trang A4.`
- `Hãy viết phần Phân tích chức năng của hệ thống NLN_PROJECT, chia thành nhóm chức năng public, admin, AI và cá nhân hóa.`
- `Hãy viết phần Cơ sở dữ liệu của project này dựa đúng schema live đã nêu ở trên.`
- `Hãy viết phần Ứng dụng AI trong hệ thống, nêu rõ vai trò của AI trong meaning, recommendation và music persona.`
- `Hãy viết phần Kết quả đạt được, Hạn chế và Hướng phát triển dựa đúng trạng thái hiện tại của project.`

---

## Kết luận

File này là bản tóm tắt thực dụng nhất để ChatGPT nắm project nhanh và hỗ trợ viết báo cáo mà không bị lệch thực trạng hệ thống.
