# PRODUCT_PLAN_PUBLIC_EXPANSION

## 1. Mục tiêu tài liệu

Tài liệu này định hướng mở rộng sản phẩm cho phần `public/` của `NLN_PROJECT` sau khi hệ thống lõi đã được ổn định qua các pha refactor trước đó.

Mục tiêu:

- đề xuất các tính năng public có giá trị sản phẩm cao, phù hợp demo
- bám sát dữ liệu và kiến trúc hiện tại của project
- tránh đề xuất các tính năng "AI cho có" nhưng không kiểm chứng được
- tạo một roadmap đủ rõ để ChatGPT/Codex hoặc người phát triển tiếp theo có thể triển khai dần mà không phá vỡ hệ thống

Tài liệu này không thay thế [README.md](C:/xampp/htdocs/NLN_PROJECT/README.md). `README.md` là tài liệu handover hệ thống hiện tại; file này là kế hoạch mở rộng sản phẩm.

---

## 2. Nguyên tắc thiết kế tính năng mới

Mọi tính năng mở rộng bên `public/` cần tuân thủ các nguyên tắc sau:

1. Bám dữ liệu thật đang có trong DB live.
2. Không bịa entity mới bằng AI nếu entity đó không tồn tại trong database.
3. AI chỉ nên dùng để:
   - phân loại
   - xếp hạng
   - diễn giải
   - tóm tắt
4. Các candidate bài hát / album / nghệ sĩ luôn phải lấy từ DB trước.
5. Mọi tính năng demo quan trọng phải có fallback không phụ thuộc API ngoài khi cần.
6. Không phá route và flow hiện có của project.
7. Ưu tiên feature có thể giải thích rõ với giáo viên về:
   - nguồn dữ liệu
   - cách tính
   - lý do dùng AI

---

## 3. Nền tảng hiện có có thể tận dụng

### 3.1. Bảng dữ liệu quan trọng

Các bảng live hiện phù hợp để mở rộng public product:

- `users`
- `songs`
- `artists`
- `albums`
- `genres`
- `search_logs`
- `artist_follows`
- `album_favorites`
- `user_recommendations`
- `comments`

### 3.2. Hành vi người dùng đã có sẵn

Project hiện đã thu thập hoặc suy ra được:

- bài hát user tìm kiếm / xem
- nghệ sĩ user theo dõi
- album user yêu thích
- bài hát được gợi ý cho user
- comment và mức độ tương tác với bài hát

### 3.3. Helper / flow hiện tại có thể tái sử dụng

- `config/recommendation_helpers.php`
  - đã có pipeline recommendation, cache và fallback
- `config/song_helpers.php`
  - dùng để resolve cover ổn định
- `config/openai_helpers.php`
  - nền cho các flow AI nếu cần
- `public/profile.php`
  - đã có khu vực personalized recommendation
- `public/includes/api_search_suggest.php`
  - có thể tái dùng một phần cho discovery flow

---

## 4. Định hướng mở rộng đề xuất

Ba hướng mở rộng phù hợp nhất cho `public/` ở thời điểm hiện tại:

1. `NLN Monthly Recap / Wrapped`
2. `AI Music Persona`
3. `Discovery Hub`

Thứ tự khuyến nghị triển khai:

1. `Monthly Recap`
2. `AI Music Persona`
3. `Discovery Hub`

Lý do:

- `Monthly Recap` tận dụng dữ liệu hiện có tốt nhất, dễ demo, ít rủi ro
- `AI Music Persona` tạo điểm nhấn AI rõ ràng, nhưng nên xây sau khi recap đã có nền data summary
- `Discovery Hub` nên làm sau cùng để dùng kết quả từ recommendation + persona + recap

---

## 5. Feature 1: NLN Monthly Recap

### 5.1. Mục tiêu sản phẩm

Tạo một trang recap theo tháng hoặc theo năm, lấy cảm hứng từ Spotify Wrapped / Apple Music Replay, giúp user xem lại hành vi nghe và khám phá nhạc của mình.

### 5.2. Giá trị với người dùng

- tăng cảm giác cá nhân hóa
- tạo lý do để user quay lại hệ thống
- rất phù hợp cho demo vì dữ liệu trực quan, dễ kể câu chuyện

### 5.3. Dữ liệu sử dụng

- `search_logs`
- `artist_follows`
- `album_favorites`
- `user_recommendations`
- `songs`
- `artists`
- `albums`
- `genres`

### 5.4. Các chỉ số khả thi cho MVP

MVP nên gồm:

- tổng số lượt tìm kiếm / xem bài hát trong tháng
- top 5 bài hát quan tâm nhiều nhất
- top 3 nghệ sĩ nổi bật
- album được quan tâm nhiều nhất
- thể loại nổi bật nhất
- ngày hoạt động nhiều nhất
- số bài hát mới user khám phá trong tháng
- một đoạn recap summary ngắn

### 5.5. Gợi ý route và file

- route mới: `public/recap.php`
- helper mới: `config/recap_helpers.php`
- nếu cần API riêng: `public/includes/api_recap_data.php`

### 5.6. Hướng triển khai kỹ thuật

1. Chọn phạm vi thời gian:
   - tháng hiện tại
   - tháng bất kỳ
   - năm hiện tại
2. Tổng hợp dữ liệu từ `search_logs` theo `user_id`
3. Join sang `songs`, `artists`, `albums`, `genres`
4. Tính các metric tổng hợp
5. Render thành các section dạng card / timeline / top list
6. Nếu dùng AI, chỉ dùng cho đoạn recap summary ngắn từ metrics đã tính sẵn

### 5.7. Non-goals

Không nên làm ngay trong MVP:

- xuất ảnh share social
- animation phức tạp dạng story slide
- recap đa năm
- so sánh với toàn hệ thống theo thời gian thực

### 5.8. Rủi ro

- `search_logs` là tín hiệu quan tâm, không phải listening log thật
- dữ liệu cũ có thể chưa đồng đều cho mọi user

### 5.9. Cách giải thích với giáo viên

Có thể trình bày rõ:

- dữ liệu hành vi được lấy từ lịch sử tìm kiếm / xem của người dùng
- recap được tính toán từ dữ liệu thật trong hệ thống
- AI chỉ dùng để viết tóm tắt recap ngắn, không tự sinh ra số liệu

---

## 6. Feature 2: AI Music Persona

### 6.1. Mục tiêu sản phẩm

Xây một trang phân loại gu âm nhạc của user thành một "persona" có thể đọc hiểu được, ví dụ:

- người nghe thiên về pop kể chuyện
- người nghe ưu tiên chart hits
- người khám phá album
- người nghe theo nghệ sĩ yêu thích

### 6.2. Giá trị với người dùng

- tăng cảm giác AI cá nhân hóa
- tạo điểm nhấn khác biệt cho project
- rất phù hợp để nối sang recommendation

### 6.3. Dữ liệu sử dụng

- `search_logs`
- `artist_follows`
- `album_favorites`
- `user_recommendations`
- `songs`
- `artists`
- `albums`
- `genres`

### 6.4. Output mong muốn

Trang persona nên trả về:

- tên persona
- mô tả ngắn 2-3 câu
- 3 đặc điểm nổi bật trong gu nhạc
- top genre
- top artist cluster
- 3 nghệ sĩ / album / bài hát nên thử tiếp

### 6.5. Gợi ý route và file

- route mới: `public/persona.php`
- helper mới: `config/persona_helpers.php`

### 6.6. Hướng triển khai kỹ thuật

1. Tạo hồ sơ sở thích từ dữ liệu DB:
   - top artists
   - top genres
   - xu hướng lặp lại / khám phá mới
   - mức độ thiên về single hay album
2. Chuẩn hóa thành một JSON profile ngắn
3. Gửi profile này cho OpenAI để:
   - phân loại persona
   - viết mô tả persona
   - diễn giải lý do
4. Cache kết quả theo user và theo chu kỳ thời gian
5. Render trang theo dạng insight card

### 6.7. Ràng buộc AI quan trọng

AI không được:

- bịa nghệ sĩ / album / bài hát không tồn tại trong DB
- tự quyết định recommendation từ khoảng trống

AI chỉ nên:

- phân tích hồ sơ sở thích
- diễn giải thành persona
- xếp hạng candidate pool đã có sẵn

### 6.8. Non-goals

Không nên làm ngay:

- persona động thay đổi theo từng ngày
- multiple personas quá chi tiết
- so sánh user với cộng đồng toàn hệ thống bằng AI

### 6.9. Rủi ro

- nếu user có quá ít dữ liệu, persona sẽ yếu
- cần fallback mô tả chung khi tín hiệu chưa đủ mạnh

---

## 7. Feature 3: Discovery Hub

### 7.1. Mục tiêu sản phẩm

Tạo một trang khám phá nội dung mới cho user, dựa trên:

- hành vi đã có
- recommendation cache
- candidate pool từ DB
- giải thích bằng AI

### 7.2. Giá trị với người dùng

- giúp recommendation trở thành một trải nghiệm riêng, không chỉ là một box trong profile
- tạo cảm giác hệ thống "hiểu" gu người dùng

### 7.3. Nội dung đề xuất cho trang

- bài hát nên nghe tiếp
- album nên thử
- nghệ sĩ nên theo dõi
- vì sao được gợi ý
- nội dung "khám phá hôm nay"

### 7.4. Gợi ý route và file

- route mới: `public/discovery.php`
- helper mới: `config/discovery_helpers.php`

### 7.5. Hướng triển khai kỹ thuật

1. Lấy user profile cơ bản
2. Sinh candidate pool từ DB:
   - songs
   - artists
   - albums
3. Loại những item user đã tương tác quá nhiều nếu cần
4. Gọi heuristic local hoặc OpenAI để rank
5. Render kết quả theo section
6. Hiển thị `because you listened to...` hoặc `vì bạn quan tâm tới...`

### 7.6. Nguyên tắc quan trọng

`Discovery Hub` không được phụ thuộc hoàn toàn vào AI runtime.

Nên có 2 lớp:

- lớp 1: candidate pool + heuristic fallback từ DB
- lớp 2: AI explanation / ranking refinement

### 7.7. Non-goals

Không nên làm ngay:

- playlist generator hoàn chỉnh
- infinite scroll discovery feed
- recommendation theo thời gian thực cho toàn site

---

## 8. Roadmap triển khai đề xuất

### Pha A. Data Foundation

Mục tiêu:

- chuẩn hóa toàn bộ metric cần dùng cho recap / persona / discovery

Việc cần làm:

- audit lại `search_logs` theo `user_id`
- định nghĩa các metric:
  - top songs
  - top artists
  - top albums
  - top genres
  - repeat vs discovery
- tạo helper dùng chung để tránh lặp query

File đề xuất:

- `config/behavior_helpers.php`

### Pha B. Monthly Recap

Mục tiêu:

- ra được một trang giá trị demo cao, không phụ thuộc AI làm lõi

Việc cần làm:

- tạo `public/recap.php`
- tạo `config/recap_helpers.php`
- thêm recap summary
- nếu cần AI, chỉ dùng cho phần narrative summary

### Pha C. AI Music Persona

Mục tiêu:

- tạo điểm nhấn AI rõ ràng và dễ giải thích

Việc cần làm:

- tạo `public/persona.php`
- tạo `config/persona_helpers.php`
- cache persona
- render persona card + explanation + recommendations

### Pha D. Discovery Hub

Mục tiêu:

- gom recommendation thành một trải nghiệm hoàn chỉnh hơn

Việc cần làm:

- tạo `public/discovery.php`
- dùng heuristic + AI explanation
- thêm section đề xuất theo songs / albums / artists

---

## 9. Đề xuất ưu tiên thực hiện ngay

Nếu chỉ chọn một feature để làm trước, nên chọn:

## `NLN Monthly Recap`

Lý do:

- dữ liệu đã có đủ để triển khai
- ít phụ thuộc API ngoài
- tính demo tốt
- dễ trình bày
- có thể mở rộng tiếp sang persona và discovery

MVP recap là bước nền tốt nhất trước khi đầu tư mạnh vào AI persona.

---

## 10. Thiết kế UX đề xuất

### 10.1. Monthly Recap

Phong cách đề xuất:

- hero ngắn, rõ trọng tâm
- stat cards
- top lists
- highlight card
- narrative summary ở cuối hoặc đầu trang

### 10.2. AI Persona

Phong cách đề xuất:

- persona badge / title lớn
- 3 insight cards
- recommended next actions
- CTA sang `Discovery Hub`

### 10.3. Discovery Hub

Phong cách đề xuất:

- section-based layout
- mỗi section có lý do gợi ý
- card nhỏ, dễ scan
- không biến thành một trang quá nặng như social feed

---

## 11. Chỉ số đánh giá thành công

Có thể đánh giá mức thành công của các feature này bằng:

- số user mở `recap.php`
- số user bấm vào item từ recap / discovery
- số lượt follow artist sau khi xem persona / discovery
- số lượt favorite album sau khi xem recommendation
- tỷ lệ refresh recommendation ở `profile.php`

Trong môi trường đồ án, các chỉ số này có thể chỉ cần log cơ bản là đủ.

---

## 12. Rủi ro sản phẩm cần tránh

1. Lạm dụng AI để sinh kết quả không kiểm chứng được.
2. Để AI trả về entity không tồn tại trong DB.
3. Tạo tính năng quá lớn nhưng dữ liệu đầu vào quá yếu.
4. Tạo UI đẹp nhưng không giải thích được logic với giáo viên.
5. Phụ thuộc quá mạnh vào API ngoài trong các luồng demo chính.

---

## 13. Kết luận

Hướng mở rộng public phù hợp nhất với `NLN_PROJECT` hiện tại là xây một cụm tính năng xoay quanh:

- `Recap`
- `Persona`
- `Discovery`

Trong đó:

- `Recap` là điểm bắt đầu tốt nhất
- `Persona` là lớp AI giải thích gu âm nhạc
- `Discovery` là lớp khai thác recommendation thành trải nghiệm sản phẩm hoàn chỉnh

Nếu tiếp tục triển khai theo roadmap này, project sẽ có một hướng phát triển rõ ràng, đúng dữ liệu thật, có chiều sâu sản phẩm, và vẫn đủ an toàn để demo.
