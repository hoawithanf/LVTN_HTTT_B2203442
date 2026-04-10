# SPEC_RECAP_PAGE

## 1. Mục tiêu

Tài liệu này đặc tả chi tiết cho feature `NLN Monthly Recap`, là bước triển khai đầu tiên sau [PRODUCT_PLAN_PUBLIC_EXPANSION.md](C:/xampp/htdocs/NLN_PROJECT/PRODUCT_PLAN_PUBLIC_EXPANSION.md).

Feature này nhằm tạo một trang recap cho người dùng ở phía `public/`, lấy cảm hứng từ Spotify Wrapped / Apple Music Replay, nhưng bám sát dữ liệu thật của `NLN_PROJECT`.

---

## 2. Mục tiêu sản phẩm

Trang recap cần trả lời được các câu hỏi sau cho user:

- tháng này tôi đã quan tâm những bài hát nào nhiều nhất?
- nghệ sĩ nào nổi bật nhất với tôi?
- album nào tôi quay lại nhiều nhất?
- thể loại nào chiếm ưu thế?
- hành vi của tôi thiên về nghe lại hay khám phá mới?

Trang này phải:

- dễ demo
- dễ giải thích nguồn dữ liệu
- không phụ thuộc AI ở phần cốt lõi
- có thể mở rộng sau này thành yearly recap

---

## 3. Phạm vi MVP

### 3.1. Route

- route mới: `public/recap.php`

### 3.2. Tham số URL

MVP nên hỗ trợ:

- `month`
- `year`

Ví dụ:

- `recap.php?month=3&year=2026`

Nếu không truyền tham số:

- mặc định dùng tháng hiện tại theo timezone server/app

### 3.3. Chỉ hỗ trợ user đã đăng nhập

Yêu cầu:

- phải có session user
- nếu chưa đăng nhập thì redirect về `public/login.php`

---

## 4. Dữ liệu nguồn

### 4.1. Bảng chính

- `search_logs`
- `songs`
- `artists`
- `albums`
- `genres`
- `album_favorites`
- `artist_follows`

### 4.2. Dữ liệu dùng trong MVP

MVP recap nên lấy trọng tâm từ `search_logs`, vì đây là tín hiệu hành vi phong phú nhất đang có trong project.

`album_favorites` và `artist_follows` có thể dùng như dữ liệu phụ để enrich recap.

### 4.3. Định nghĩa hành vi

Trong bối cảnh project hiện tại:

- một dòng `search_logs` được coi là một lần user quan tâm / truy cập bài hát
- recap không được mô tả là "nghe thực sự" nếu hệ thống không có playback log

Ngôn ngữ hiển thị nên dùng:

- `quan tâm`
- `tìm kiếm`
- `xem`
- `khám phá`

Tránh dùng:

- `phát nhạc`
- `stream`
- `nghe trực tuyến`

---

## 5. Chỉ số cần hiển thị trong MVP

## 5.1. Hero Summary

Một đoạn ngắn ở đầu trang:

- tiêu đề recap theo tháng
- mô tả ngắn về hành vi âm nhạc của user trong tháng đó

Ví dụ:

- `Recap tháng 3/2026`
- `Bạn đã khám phá 42 lượt bài hát, tập trung nhiều vào Pop và các nghệ sĩ nữ nổi bật.`

### 5.2. Metric Cards

Hiển thị 4 metric card đầu trang:

- tổng lượt quan tâm bài hát
- số bài hát khác nhau đã xem
- số nghệ sĩ khác nhau đã quan tâm
- số album khác nhau đã chạm tới

### 5.3. Top Songs

Top 5 bài hát nổi bật theo tháng:

- cover
- title
- artist
- album
- số lượt quan tâm trong tháng

### 5.4. Top Artists

Top 3 nghệ sĩ nổi bật:

- artist image
- artist name
- số lượt quan tâm liên quan
- link sang `artist.php?id=...`

### 5.5. Top Album

Hiển thị album nổi bật nhất tháng:

- cover
- tên album
- artist
- số lượt liên quan
- link sang `album.php?id=...`

### 5.6. Top Genre

Hiển thị thể loại nổi bật nhất:

- genre name
- tỷ trọng trong tháng

### 5.7. Discovery Insight

Một insight đơn giản:

- số bài hát user chỉ xem 1 lần
- số bài hát user quay lại nhiều lần
- kết luận: thiên về `khám phá` hay `nghe lại`

---

## 6. Logic tính toán

### 6.1. Bộ lọc thời gian

Lọc `search_logs` theo:

- `user_id`
- `MONTH(created_at)`
- `YEAR(created_at)`

Nếu `search_logs.created_at` là DATETIME chuẩn thì dùng filter SQL.

### 6.2. Tổng lượt quan tâm

Đếm số dòng trong `search_logs` của user trong tháng.

### 6.3. Số bài hát khác nhau

`COUNT(DISTINCT song_id)`

### 6.4. Số nghệ sĩ khác nhau

Join `songs.artist_id` rồi:

- `COUNT(DISTINCT songs.artist_id)`

### 6.5. Số album khác nhau

Join `songs.album_id` rồi:

- `COUNT(DISTINCT songs.album_id)`

### 6.6. Top songs

Group by `song_id`, order by:

1. `COUNT(*) DESC`
2. `MAX(search_logs.created_at) DESC`

### 6.7. Top artists

Group theo `songs.artist_id` trên tập `search_logs` của user.

### 6.8. Top album

Group theo `songs.album_id` trên tập `search_logs` của user.

### 6.9. Top genre

Dựa trên `songs.genre_id`.

Lưu ý:

- không dùng `song_genres`, vì bảng này không tồn tại trong DB live

### 6.10. Discovery vs Repeat

Có thể tạm định nghĩa:

- `repeat_count`: số lượt thuộc các bài hát có tổng lượt >= 2 trong tháng
- `discovery_count`: số bài hát chỉ xuất hiện đúng 1 lần trong tháng

Hoặc đơn giản hơn cho MVP:

- `repeat_songs = số bài có COUNT(*) >= 2`
- `new_songs = số bài có COUNT(*) = 1`

Nếu `repeat_songs > new_songs`:

- user thiên về `nghe lại`

Ngược lại:

- user thiên về `khám phá`

---

## 7. Query/Helper đề xuất

### 7.1. File helper mới

- `config/recap_helpers.php`

### 7.2. Hàm đề xuất

```php
nln_get_recap_period(int $month, int $year): array
nln_get_user_monthly_recap(mysqli $conn, int $userId, int $month, int $year): array
nln_get_recap_summary_text(array $recap): string
```

### 7.3. Cấu trúc dữ liệu trả về đề xuất

```php
[
    'period' => [
        'month' => 3,
        'year' => 2026,
        'label' => 'Tháng 3/2026',
    ],
    'metrics' => [
        'total_views' => 42,
        'unique_songs' => 18,
        'unique_artists' => 7,
        'unique_albums' => 6,
    ],
    'top_songs' => [...],
    'top_artists' => [...],
    'top_album' => [...],
    'top_genre' => [...],
    'insights' => [
        'repeat_songs' => 8,
        'discovery_songs' => 10,
        'listening_style' => 'discovery',
    ],
    'summary_text' => '...',
]
```

---

## 8. AI có dùng hay không?

### 8.1. Khuyến nghị cho MVP

MVP không cần phụ thuộc OpenAI để trang recap hoạt động.

Nên làm:

- toàn bộ số liệu bằng SQL/helper local
- `summary_text` sinh bằng rule-based template trước

Ví dụ:

- nếu top genre là Pop và user khám phá nhiều bài mới thì summary là một mẫu câu cố định

### 8.2. Giai đoạn sau

Sau khi MVP ổn định, có thể thêm:

- OpenAI để viết recap narrative tự nhiên hơn

Nhưng AI chỉ được nhận:

- metrics đã tính
- top artists / genres / top songs

AI không được phép tự sinh số liệu mới.

---

## 9. UX/UI đề xuất

### 9.1. Ngôn ngữ thiết kế

Trang recap phải đi theo ngôn ngữ thiết kế hiện tại của public:

- bo góc mềm
- card trắng / kính nhẹ
- spacing gọn, hiện đại
- typography rõ ràng

Có thể lấy cảm hứng từ:

- `public/search.php`
- `public/charts.php`
- `public/profile.php`

### 9.2. Cấu trúc trang đề xuất

1. Hero recap
2. Metric cards
3. Top songs
4. Top artists + top album
5. Genre + discovery insight
6. CTA sang recommendation / discovery

### 9.3. CTA cuối trang

Đề xuất thêm CTA:

- `Xem gợi ý dành cho bạn`
- `Khám phá thêm nghệ sĩ`

Nhưng không nên thêm quá nhiều CTA trong MVP.

---

## 10. Empty State

Nếu user không có đủ dữ liệu trong tháng:

- không render trang trắng
- không hiện lỗi kỹ thuật

Phải có empty state rõ ràng:

- `Bạn chưa có đủ hoạt động trong tháng này để tạo recap. Hãy khám phá thêm bài hát và quay lại sau.`

Có thể kèm CTA:

- `Tìm bài hát`
- `Xem bảng xếp hạng`

---

## 11. Bảo toàn kiến trúc hiện tại

Khi triển khai `recap.php`, phải tuân thủ:

- không đụng `post.php` nếu không cần
- không chỉnh schema live hiện tại nếu chưa thật sự cần
- không tạo phụ thuộc mới vào bảng không tồn tại
- không dùng `song_genres`
- dùng helper cover hiện có nếu cần hiển thị ảnh bài hát

Nếu cần cover bài hát:

- ưu tiên tái sử dụng `config/song_helpers.php`

---

## 12. Kiểm thử tay đề xuất

Sau khi triển khai:

1. Đăng nhập bằng user có `search_logs`
2. Mở `recap.php`
3. Kiểm tra metric có khớp dữ liệu thực tế
4. Kiểm tra top song / top artist / top album có đúng
5. Thử tháng không có dữ liệu để kiểm tra empty state
6. Kiểm tra link sang:
   - `post.php`
   - `artist.php`
   - `album.php`
7. Kiểm tra responsive trên desktop/mobile

---

## 13. Các bước triển khai tiếp theo

Sau file đặc tả này, trình tự nên là:

1. tạo `config/recap_helpers.php`
2. dựng `public/recap.php`
3. render MVP bằng dữ liệu local
4. test dữ liệu live
5. sau cùng mới cân nhắc thêm AI summary

---

## 14. Kết luận

`Monthly Recap` là feature public nên làm đầu tiên vì:

- có dữ liệu nền phù hợp
- ít rủi ro hơn persona/discovery
- dễ demo
- dễ giải thích
- có thể làm xong một bản MVP sạch mà không cần phụ thuộc API ngoài

Khi feature này hoàn tất, nó sẽ trở thành nền dữ liệu và nền trải nghiệm rất tốt cho:

- `AI Music Persona`
- `Discovery Hub`
