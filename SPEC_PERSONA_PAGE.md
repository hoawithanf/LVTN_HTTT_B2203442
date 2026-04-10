# SPEC_PERSONA_PAGE

## 1. Mục tiêu

Tài liệu này đặc tả chi tiết cho feature `AI Music Persona`, là bước tiếp theo sau `Monthly Recap` trong roadmap mở rộng public của `NLN_PROJECT`.

Feature này nhằm tạo một trang phân tích gu âm nhạc của user bằng AI, nhưng phải bám sát dữ liệu thật của hệ thống và không cho AI tự bịa nghệ sĩ, album hay bài hát ngoài database.

---

## 2. Mục tiêu sản phẩm

Trang persona cần trả lời được:

- user này có xu hướng nghe gì?
- gu nhạc thiên về nghệ sĩ nào, thể loại nào, kiểu khám phá nào?
- có thể mô tả gu nhạc của user bằng một persona dễ hiểu hay không?
- từ gu đó, nên gợi ý user đi tiếp theo hướng nào?

Trang này phải:

- tạo cảm giác cá nhân hóa rõ ràng
- có điểm nhấn AI nhưng vẫn kiểm chứng được
- dễ giải thích về input và output
- có fallback khi AI không sẵn sàng

---

## 3. Phạm vi MVP

### 3.1. Route

- route mới: `public/persona.php`

### 3.2. Chỉ hỗ trợ user đã đăng nhập

Yêu cầu:

- phải có session user
- nếu chưa đăng nhập thì redirect về `public/login.php`

### 3.3. Mục tiêu của MVP

MVP persona chỉ cần làm tốt 4 việc:

1. gom hồ sơ hành vi âm nhạc của user
2. phân loại thành một persona dễ hiểu
3. hiển thị insight rõ ràng
4. gợi ý tiếp các artist / album / songs từ candidate pool trong DB

Không cần làm ở MVP:

- nhiều persona phụ cùng lúc
- animation phức tạp
- so sánh user với toàn bộ cộng đồng

---

## 4. Dữ liệu nguồn

### 4.1. Bảng dữ liệu dùng trực tiếp

- `search_logs`
- `artist_follows`
- `album_favorites`
- `user_recommendations`
- `songs`
- `artists`
- `albums`
- `genres`

### 4.2. Helper hiện tại có thể tận dụng

- `config/recommendation_helpers.php`
- `config/openai_helpers.php`
- `config/song_helpers.php`

### 4.3. Định nghĩa dữ liệu hành vi

Trong project hiện tại:

- `search_logs` phản ánh mức độ quan tâm / xem / tìm kiếm
- `artist_follows` phản ánh thiên hướng quan tâm nghệ sĩ dài hạn
- `album_favorites` phản ánh thiên hướng quay lại album
- `user_recommendations` phản ánh lịch sử gợi ý đã từng được xếp hạng

Vì hệ thống không có playback log chuẩn, persona không nên tự nhận là:

- "nghe 1200 phút"
- "stream nhiều"

Nên dùng wording:

- `quan tâm`
- `khám phá`
- `quay lại`
- `ưu tiên`

---

## 5. Output người dùng sẽ thấy

Trang persona nên gồm:

### 5.1. Persona Title

Ví dụ:

- `Story-Driven Pop Listener`
- `Album Explorer`
- `Chart-Oriented Listener`
- `Artist-Loyal Fan`

Persona title có thể:

- bằng tiếng Anh ngắn gọn
- hoặc tiếng Việt ngắn gọn

Khuyến nghị:

- title ngắn
- description tiếng Việt

### 5.2. Persona Description

Một đoạn 2-3 câu:

- mô tả gu nhạc chính
- nêu điểm nổi bật nhất
- diễn giải vì sao hệ thống phân loại như vậy

### 5.3. Insight Cards

Ít nhất 3 insight card:

- thể loại nổi trội
- nghệ sĩ trội nhất
- xu hướng `khám phá` vs `quay lại`

Có thể thêm:

- thiên về album hay single
- mức độ tập trung vào 1 nghệ sĩ hay nhiều nghệ sĩ

### 5.4. Recommendation Blocks

3 block đề xuất:

- nghệ sĩ nên theo dõi tiếp
- album nên thử
- bài hát nên nghe tiếp

### 5.5. Why This Persona

Một box giải thích ngắn:

- hệ thống đã dựa vào những tín hiệu nào

Ví dụ:

- top genre
- số lượng artist theo dõi
- lịch sử tìm kiếm tập trung vào cùng artist

---

## 6. Kiến trúc AI an toàn

## 6.1. Quy tắc quan trọng nhất

AI không được phép tự sinh entity mới ngoài DB.

Điều này áp dụng cho:

- artist
- album
- song
- genre

### 6.2. AI chỉ được làm gì

AI chỉ nên:

- đọc hồ sơ hành vi đã được rút gọn
- đặt tên persona
- viết mô tả persona
- diễn giải insight
- xếp hạng nhẹ candidate pool đã có sẵn

### 6.3. AI không được làm gì

AI không được:

- bịa bài hát không có trong DB
- bịa album không có trong DB
- tự sinh số liệu không có trong helper
- tự kết luận quá mức về tâm lý người dùng

### 6.4. Fallback khi AI lỗi

Nếu OpenAI lỗi hoặc timeout:

- persona vẫn phải render được
- fallback sang persona rule-based local
- recommendation vẫn lấy từ candidate pool local / cached

---

## 7. Dữ liệu profile đầu vào

### 7.1. Helper mới đề xuất

- `config/persona_helpers.php`

### 7.2. Các hàm đề xuất

```php
nln_build_user_music_profile(mysqli $conn, int $userId): array
nln_classify_music_persona_local(array $profile): array
nln_classify_music_persona_ai(array $profile, array $candidatePool = []): array
nln_get_user_persona(mysqli $conn, int $userId, bool $refresh = false): array
```

### 7.3. Cấu trúc profile đầu vào đề xuất

```php
[
    'user_id' => 2,
    'top_genres' => [
        ['genre_id' => 1, 'genre_name' => 'Pop', 'score' => 72],
    ],
    'top_artists' => [
        ['artist_id' => 1, 'artist_name' => 'Taylor Swift', 'score' => 71],
    ],
    'top_albums' => [
        ['album_id' => 11, 'album_name' => 'The Tortured Poets Department', 'score' => 19],
    ],
    'behavior' => [
        'total_searches' => 150,
        'unique_songs' => 40,
        'repeat_songs' => 18,
        'discovery_songs' => 22,
        'followed_artists' => 3,
        'favorite_albums' => 5,
    ],
    'signals' => [
        'is_artist_loyal' => true,
        'is_album_oriented' => false,
        'is_discovery_heavy' => true,
    ],
]
```

---

## 8. Candidate pool cho recommendation

### 8.1. Candidate pool là bắt buộc

Nếu persona page có đề xuất artist / album / song, các item đó phải được lấy từ DB trước.

### 8.2. Candidate pool có thể lấy từ

- `user_recommendations`
- heuristic từ `recommendation_helpers.php`
- top artist / album cùng genre
- nội dung chưa được follow / favorite bởi user

### 8.3. Cấu trúc đề xuất

```php
[
    'songs' => [...],
    'artists' => [...],
    'albums' => [...],
]
```

AI nếu có dùng chỉ được:

- sắp xếp
- chọn ra top items
- giải thích ngắn vì sao phù hợp

---

## 9. Persona taxonomy đề xuất

Để tránh AI sinh persona quá lung tung, MVP nên giới hạn trong một taxonomy nhỏ.

Khuyến nghị 5 persona ban đầu:

1. `Story-Driven Pop Listener`
2. `Album Explorer`
3. `Artist-Loyal Fan`
4. `Chart-Focused Listener`
5. `Balanced Music Explorer`

### 9.1. Rule-based mapping mẫu

Ví dụ:

- nếu top artist chiếm tỷ trọng rất cao và followed artists có dữ liệu mạnh:
  - `Artist-Loyal Fan`

- nếu album favorites cao và top album repeat mạnh:
  - `Album Explorer`

- nếu Pop chiếm ưu thế và top content tập trung vào mainstream artist:
  - `Story-Driven Pop Listener`

- nếu top songs trùng mạnh với charts:
  - `Chart-Focused Listener`

- nếu dữ liệu phân tán tương đối đều:
  - `Balanced Music Explorer`

AI có thể tinh chỉnh title/description, nhưng không nên phá taxonomy này ở MVP.

---

## 10. Caching

### 10.1. Có cần cache không?

Có.

Persona là một kết quả phân tích tương đối ổn định, không cần gọi AI ở mỗi request.

### 10.2. Hướng cache đề xuất

Có thể dùng một trong 2 hướng:

1. cache vào DB
2. cache file/json tạm thời

Khuyến nghị thực tế hơn:

- thêm bảng `user_personas` nếu chấp nhận mở schema

Nếu chưa muốn đổi schema ngay:

- cache mềm theo session hoặc file tạm

### 10.3. Gợi ý schema nếu mở rộng DB

```sql
user_personas
- persona_id
- user_id
- persona_key
- persona_title
- persona_description
- insight_json
- recommendation_json
- source
- generated_at
```

Lưu ý:

- đây là hướng mở rộng, không bắt buộc cho MVP đầu tiên

---

## 11. Giao diện đề xuất

### 11.1. Route page

- `public/persona.php`

### 11.2. Bố cục đề xuất

1. Hero persona
2. Persona description
3. 3 insight cards
4. Recommendation sections
5. Why this persona
6. CTA sang `recap.php` và `profile.php`

### 11.3. Ngôn ngữ thiết kế

Page này nên đi theo cùng hệ thiết kế với:

- `search.php`
- `profile.php`
- `recap.php`

Đặc điểm:

- bo góc mềm
- panel trắng sáng
- spacing gọn
- chip/pill rõ ràng
- typography hiện đại

---

## 12. Empty State

Nếu user chưa có đủ dữ liệu:

- không gọi AI mù
- hiển thị empty state rõ ràng

Ví dụ:

- `Bạn chưa có đủ dữ liệu để tạo chân dung âm nhạc cá nhân. Hãy tiếp tục tìm kiếm, theo dõi nghệ sĩ hoặc lưu album yêu thích rồi quay lại sau.`

CTA:

- `Tìm bài hát`
- `Xem Recap`
- `Khám phá charts`

---

## 13. Prompt strategy đề xuất

Nếu dùng OpenAI, prompt phải giới hạn rất rõ.

### 13.1. Input cho AI

Chỉ gửi:

- top genres
- top artists
- top albums
- repeat/discovery stats
- candidate pool đã lọc sẵn

### 13.2. Output schema AI mong muốn

```json
{
  "persona_key": "artist_loyal_fan",
  "persona_title": "Artist-Loyal Fan",
  "persona_description": "...",
  "insights": [
    "...",
    "...",
    "..."
  ],
  "recommended_song_ids": [48, 53, 254],
  "recommended_artist_ids": [1, 8],
  "recommended_album_ids": [11, 10]
}
```

### 13.3. Validation bắt buộc

Sau khi AI trả về:

- validate `persona_key`
- validate các ID có thực trong candidate pool
- loại bỏ item không hợp lệ
- nếu output lỗi, fallback sang local persona

---

## 14. Logic local fallback

MVP persona phải có local fallback.

### 14.1. Tối thiểu local fallback phải trả được

- `persona_key`
- `persona_title`
- `persona_description`
- `insight cards`
- `candidate recommendations`

### 14.2. Ví dụ

Nếu:

- top genre là Pop
- top artist rất áp đảo
- repeat_songs cao

thì local fallback có thể trả:

- `Artist-Loyal Fan`
- mô tả ngắn viết theo template

---

## 15. Kiểm thử tay đề xuất

Sau khi triển khai:

1. test với user có nhiều `search_logs`
2. test với user có ít dữ liệu
3. test khi OpenAI khả dụng
4. test khi OpenAI lỗi / timeout
5. kiểm tra AI output không chứa ID ngoài DB
6. kiểm tra CTA link:
   - `profile.php`
   - `recap.php`
   - `artist.php`
   - `album.php`
   - `post.php`

---

## 16. Rủi ro cần tránh

1. Để AI "bịa" persona quá mức, không bám dữ liệu thật.
2. Để AI tự gợi ý item ngoài database.
3. Để page persona phụ thuộc hoàn toàn vào OpenAI runtime.
4. Dùng wording như thể hệ thống có playback log chuẩn trong khi hiện chỉ có search/view signals.
5. Dùng persona quá trừu tượng, khó giải thích với giáo viên.

---

## 17. Trình tự triển khai đề xuất

Sau tài liệu này, thứ tự nên là:

1. tạo `config/persona_helpers.php`
2. triển khai local persona classifier
3. dựng `public/persona.php`
4. test local-only flow
5. sau đó mới nối thêm OpenAI classification
6. cuối cùng thêm cache/refresh persona

---

## 18. Kết luận

`AI Music Persona` là feature phù hợp để tạo chiều sâu AI cho `NLN_PROJECT`, nhưng chỉ nên triển khai theo hướng:

- dữ liệu thật trước
- heuristic local trước
- AI diễn giải và tinh chỉnh sau

Nếu làm đúng theo đặc tả này, feature sẽ:

- hợp lý về kỹ thuật
- dễ demo
- dễ giải thích
- không phá tính tin cậy của hệ thống
