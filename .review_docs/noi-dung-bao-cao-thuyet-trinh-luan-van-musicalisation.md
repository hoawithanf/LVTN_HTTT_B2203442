# NỘI DUNG BÁO CÁO THUYẾT TRÌNH LUẬN VĂN

## Đề tài
**Xây dựng website tra cứu lời và ý nghĩa bài hát và gợi ý thông minh**

## Tên hệ thống triển khai
**Musicalisation**

## 1. Phần chào hỏi và mở đầu

Kính thưa quý thầy cô trong Hội đồng,

Em xin tự giới thiệu, em là **Trịnh Ngọc Hòa**, sinh viên ngành **Hệ thống thông tin**. Hôm nay, em xin trình bày nội dung luận văn tốt nghiệp với đề tài **“Xây dựng website tra cứu lời và ý nghĩa bài hát và gợi ý thông minh”**.

Trong quá trình sử dụng các nền tảng âm nhạc hiện nay, em nhận thấy người dùng không chỉ có nhu cầu nghe nhạc mà còn muốn **tra cứu lời bài hát, tìm hiểu ý nghĩa nội dung, xem thông tin nghệ sĩ, theo dõi xu hướng âm nhạc và nhận được các gợi ý phù hợp với sở thích cá nhân**. Tuy nhiên, nhiều hệ thống hiện tại vẫn còn rời rạc, chủ yếu cung cấp thông tin cơ bản, chưa hỗ trợ tốt cho việc phân tích nội dung bài hát và cá nhân hóa trải nghiệm người dùng.

Từ thực tế đó, em xây dựng hệ thống **Musicalisation** nhằm tạo ra một nền tảng web hỗ trợ **tra cứu lời bài hát, phân tích ý nghĩa bằng AI, cá nhân hóa nội dung và quản trị dữ liệu âm nhạc** trên cùng một hệ thống.

## 2. Tóm tắt đề tài

Đề tài hướng đến việc xây dựng một website âm nhạc có khả năng:

- Tìm kiếm bài hát và nghệ sĩ.
- Xem thông tin chi tiết bài hát bao gồm **lyrics, meaning và video liên quan**.
- Phân tích ý nghĩa bài hát bằng **AI**.
- Gợi ý bài hát cá nhân hóa dựa trên hành vi người dùng.
- Hiển thị **recap** và **music persona**.
- Hỗ trợ các tương tác như **bình luận, phản hồi bình luận, theo dõi nghệ sĩ, yêu thích album**.
- Hỗ trợ phân hệ **quản trị** và **xuất báo cáo CSV**.

Về mặt công nghệ, hệ thống hiện tại được xây dựng bằng:

- **PHP** cho lớp xử lý nghiệp vụ.
- **MySQL/MariaDB** cho lớp dữ liệu.
- **XAMPP** cho môi trường triển khai cục bộ.
- Giao diện web bằng **HTML, CSS, JavaScript, Bootstrap**.
- Tích hợp API ngoài như **lyrics.ovh**, **YouTube Data API** và **OpenAI API**.

## 3. Mục tiêu nghiên cứu

Mục tiêu của đề tài gồm 3 nhóm chính:

**Thứ nhất**, xây dựng một hệ thống web cho phép người dùng tra cứu thông tin âm nhạc nhanh chóng và thuận tiện.

**Thứ hai**, tích hợp trí tuệ nhân tạo để hỗ trợ người dùng hiểu sâu hơn nội dung bài hát thông qua chức năng phân tích ý nghĩa.

**Thứ ba**, nâng cao trải nghiệm người dùng bằng cơ chế cá nhân hóa, cụ thể là gợi ý bài hát, recap và music persona dựa trên dữ liệu hành vi.

## 4. Các nghiên cứu và hệ thống liên quan

Trong quá trình khảo sát, em nhận thấy có thể chia các hệ thống liên quan thành 3 nhóm:

- **Nhóm tra cứu lời bài hát**, ví dụ các website lyrics, có ưu điểm là dữ liệu phong phú nhưng thường thiếu cá nhân hóa.
- **Nhóm nghe nhạc trực tuyến**, ví dụ Spotify, YouTube Music, Apple Music, có khả năng gợi ý tốt nhưng không tập trung vào việc giải thích nội dung bài hát.
- **Nhóm ứng dụng AI hỗ trợ nội dung**, có thể phân tích văn bản hoặc hỗ trợ hỏi đáp, nhưng thường chưa tích hợp trực tiếp với ngữ cảnh dữ liệu âm nhạc.

Từ đó, đề tài này hướng đến một cách tiếp cận kết hợp, tức là:

- vừa **tra cứu nội dung âm nhạc**,
- vừa **phân tích lyrics bằng AI**,
- vừa **cá nhân hóa nội dung dựa trên hành vi người dùng**,
- đồng thời có **phân hệ quản trị dữ liệu**.

Điểm khác biệt của đề tài không nằm ở một chức năng đơn lẻ, mà nằm ở việc **tích hợp nhiều lớp chức năng trên cùng một hệ thống web**.

## 5. Hướng xây dựng hệ thống

Hệ thống Musicalisation được xây dựng theo hướng:

- tổ chức theo **mô hình kiến trúc 3 lớp**,
- vận hành theo mô hình **Client – Server**,
- xử lý dữ liệu tập trung,
- và tích hợp AI như một thành phần hỗ trợ trong lớp nghiệp vụ.

Ba lớp chính gồm:

### 5.1. Lớp giao diện

Đây là lớp người dùng và quản trị viên tương tác trực tiếp thông qua trình duyệt web. Lớp này gồm:

- trang chủ,
- tìm kiếm,
- trang chi tiết bài hát,
- trang nghệ sĩ, album, tin tức,
- trang hồ sơ người dùng,
- recap,
- persona,
- và giao diện quản trị.

Nhiệm vụ của lớp này là:

- nhận thao tác người dùng,
- gửi request đến lớp nghiệp vụ,
- và hiển thị kết quả trả về.

### 5.2. Lớp nghiệp vụ

Đây là lớp trung tâm của hệ thống, được xây dựng bằng PHP. Lớp này thực hiện:

- xác thực và phân quyền,
- xử lý tìm kiếm,
- lấy dữ liệu bài hát,
- xử lý bình luận,
- theo dõi nghệ sĩ,
- yêu thích album,
- sinh gợi ý,
- tính recap,
- xác định persona,
- gọi AI để phân tích ý nghĩa bài hát,
- và hỗ trợ xuất báo cáo CSV.

Lớp này cũng là nơi giao tiếp với các API ngoài.

### 5.3. Lớp dữ liệu

Đây là nơi lưu trữ và quản lý dữ liệu hệ thống bằng MySQL/MariaDB. Dữ liệu chính bao gồm:

- người dùng,
- bài hát,
- nghệ sĩ,
- album,
- thể loại,
- tin tức,
- bình luận,
- theo dõi nghệ sĩ,
- yêu thích album,
- thông báo,
- lịch sử tìm kiếm,
- dữ liệu gợi ý,
- và một số tài nguyên media cục bộ.

## 6. Luồng xử lý dữ liệu và lưu trữ

Đây là phần quan trọng nhất của đề tài vì thể hiện bản chất của hệ thống thông tin.

### 6.1. Luồng dữ liệu tra cứu bài hát

Khi người dùng nhập từ khóa tìm kiếm:

1. Từ khóa được gửi từ giao diện web đến lớp nghiệp vụ.
2. Lớp nghiệp vụ kiểm tra dữ liệu đầu vào và thực hiện truy vấn cơ sở dữ liệu.
3. Hệ thống tìm các bài hát hoặc nghệ sĩ phù hợp.
4. Kết quả được trả về giao diện dưới dạng HTML hoặc JSON.
5. Nếu người dùng đã đăng nhập, lịch sử tìm kiếm được ghi nhận để phục vụ thống kê và cá nhân hóa.

### 6.2. Luồng dữ liệu xem chi tiết bài hát

Khi người dùng chọn một bài hát:

1. Giao diện gửi yêu cầu kèm mã bài hát.
2. Lớp nghiệp vụ truy vấn dữ liệu bài hát từ cơ sở dữ liệu.
3. Hệ thống lấy thêm lyrics, meaning hoặc video liên quan nếu cần.
4. Dữ liệu sau xử lý được tổng hợp và trả về trang chi tiết.

Nội dung hiển thị gồm:

- tên bài hát,
- lyrics,
- meaning,
- video,
- nghệ sĩ,
- album,
- và các bài hát liên quan.

### 6.3. Luồng dữ liệu AI phân tích ý nghĩa bài hát

Khi người dùng nhấn chức năng phân tích bằng AI:

1. Hệ thống lấy lyrics của bài hát.
2. Nội dung lyrics được gửi từ lớp nghiệp vụ đến **OpenAI API**.
3. Dịch vụ AI trả về phần phân tích dưới dạng văn bản.
4. Hệ thống kiểm tra, xử lý định dạng và hiển thị kết quả cho người dùng.

Điểm cần nhấn mạnh ở đây là:

- AI không thay thế cơ sở dữ liệu,
- AI chỉ là một lớp hỗ trợ diễn giải nội dung,
- dữ liệu lõi vẫn do hệ thống quản lý.

### 6.4. Luồng dữ liệu recommendation

Chức năng gợi ý được xây dựng dựa trên dữ liệu hành vi của người dùng, cụ thể như:

- lịch sử tìm kiếm,
- nghệ sĩ đã theo dõi,
- album đã yêu thích,
- và các tương tác nội dung trước đó.

Luồng xử lý gồm:

1. Hệ thống thu thập dữ liệu hành vi từ cơ sở dữ liệu.
2. Lớp nghiệp vụ phân tích các tín hiệu sở thích.
3. Hệ thống chấm điểm hoặc chọn các bài hát phù hợp.
4. Danh sách gợi ý được lưu hoặc cập nhật trong dữ liệu recommendation.
5. Kết quả được trả về giao diện hồ sơ người dùng.

### 6.5. Luồng dữ liệu recap và persona

Hai chức năng này dùng chung nền tảng dữ liệu hành vi.

- **Recap** tập trung tổng hợp số liệu theo thời gian, ví dụ số lượt tìm kiếm, bài hát nổi bật, nghệ sĩ được quan tâm nhiều.
- **Persona** tập trung phân loại gu âm nhạc của người dùng dựa trên tín hiệu hành vi và đặc trưng nội dung.

Điểm cần nhấn mạnh khi thuyết trình:

- recap thiên về **tổng hợp và thống kê**,
- persona thiên về **diễn giải và phân loại phong cách nghe nhạc**.

### 6.6. Luồng dữ liệu tương tác và quản trị

Đối với bình luận, theo dõi nghệ sĩ, yêu thích album:

- giao diện gửi hành động người dùng,
- lớp nghiệp vụ kiểm tra quyền,
- dữ liệu được ghi xuống cơ sở dữ liệu,
- kết quả trả về ngay trên giao diện.

Đối với quản trị:

- quản trị viên đăng nhập vào phân hệ admin,
- thực hiện CRUD trên người dùng, nghệ sĩ, bài hát, tin tức,
- hệ thống cập nhật cơ sở dữ liệu,
- có thể thống kê và xuất báo cáo CSV.

## 7. Mô hình dữ liệu và lưu trữ

Hệ thống có 3 mức nhìn dữ liệu:

- mức khái niệm để mô tả thực thể và quan hệ,
- mức luận lý để biểu diễn cấu trúc bảng,
- và mức triển khai thực tế trong MySQL/MariaDB.

Các nhóm dữ liệu chính gồm:

- **Dữ liệu người dùng**: phục vụ xác thực, phân quyền, hồ sơ.
- **Dữ liệu nội dung âm nhạc**: bài hát, nghệ sĩ, album, thể loại, tin tức.
- **Dữ liệu tương tác**: bình luận, theo dõi nghệ sĩ, yêu thích album, thông báo.
- **Dữ liệu phân tích**: lịch sử tìm kiếm, recommendation, recap, persona.

Một điểm cần lưu ý là recommendation, recap và persona không hoàn toàn là dữ liệu tĩnh gốc, mà phần lớn là **dữ liệu được suy ra từ hành vi**.

## 8. Kết quả đạt được

Sau quá trình nghiên cứu và triển khai, hệ thống đã đạt được các kết quả chính:

- Xây dựng thành công website tra cứu bài hát và nghệ sĩ trên nền tảng web.
- Hiển thị được lyrics, meaning và video liên quan.
- Tích hợp AI để phân tích ý nghĩa bài hát.
- Triển khai gợi ý cá nhân hóa theo hành vi người dùng.
- Xây dựng được recap và music persona.
- Hỗ trợ bình luận, phản hồi bình luận, theo dõi nghệ sĩ, yêu thích album.
- Xây dựng phân hệ quản trị dữ liệu.
- Hỗ trợ lọc và xuất báo cáo CSV.

Nhìn chung, hệ thống không chỉ dừng ở mức tra cứu dữ liệu, mà đã mở rộng sang hướng **hệ thống âm nhạc có hỗ trợ AI và cá nhân hóa**.

## 9. Ưu điểm của hệ thống

Các ưu điểm nổi bật gồm:

- Giao diện web dễ tiếp cận, triển khai đơn giản trên XAMPP.
- Kiến trúc 3 lớp rõ ràng, thuận lợi cho bảo trì và mở rộng.
- Kết hợp được dữ liệu âm nhạc, AI và cá nhân hóa trong cùng một hệ thống.
- Hỗ trợ cả người dùng cuối và quản trị viên.
- Có thể mở rộng theo hướng recommendation nâng cao, dashboard và mobile app.

## 10. Hạn chế của hệ thống

Bên cạnh kết quả đạt được, hệ thống vẫn còn một số hạn chế:

- Phụ thuộc vào API ngoài như lyrics.ovh, YouTube Data API, OpenAI API.
- Chất lượng phân tích AI phụ thuộc vào dữ liệu lyrics đầu vào.
- Recommendation hiện chủ yếu dựa trên luật và dữ liệu hành vi cơ bản.
- Chưa tối ưu cho tải lớn hoặc môi trường sản xuất thực tế.
- Hiện tại mới triển khai trên nền tảng web, chưa có ứng dụng di động.

## 11. Hướng phát triển

Trong tương lai, hệ thống có thể được phát triển theo các hướng sau:

- Nâng cấp recommendation bằng các mô hình Machine Learning như Collaborative Filtering.
- Tăng chất lượng phân tích AI bằng prompt tốt hơn hoặc mô hình mạnh hơn.
- Phát triển dashboard quản trị trực quan hơn.
- Mở rộng playlist, chia sẻ bài hát và cộng đồng người dùng.
- Xây dựng phiên bản mobile app.
- Tối ưu hiệu năng, bảo mật và khả năng mở rộng.

## 12. Phần kết thúc bài báo cáo

Kính thưa quý thầy cô,

Trên đây là phần trình bày của em về đề tài **“Xây dựng website tra cứu lời và ý nghĩa bài hát và gợi ý thông minh”** với sản phẩm triển khai là hệ thống **Musicalisation**.

Thông qua đề tài này, em đã có cơ hội vận dụng kiến thức về phân tích thiết kế hệ thống, cơ sở dữ liệu, lập trình web, tích hợp API và ứng dụng AI vào một bài toán có tính thực tiễn. Mặc dù hệ thống vẫn còn những hạn chế nhất định, nhưng em tin rằng đây là một nền tảng có thể tiếp tục phát triển và hoàn thiện trong tương lai.

Em xin chân thành cảm ơn quý thầy cô đã lắng nghe phần trình bày của em. Em rất mong nhận được ý kiến nhận xét và góp ý từ Hội đồng.

## 13. Câu hỏi hội đồng có thể hỏi và gợi ý trả lời

### 13.1. Vì sao em chọn đề tài này?

**Gợi ý trả lời:**

Em chọn đề tài này vì nhu cầu tra cứu thông tin âm nhạc hiện nay là rất lớn, nhưng nhiều hệ thống vẫn chỉ cung cấp thông tin rời rạc và chưa hỗ trợ tốt cho việc phân tích nội dung bài hát cũng như cá nhân hóa trải nghiệm. Em muốn xây dựng một hệ thống tích hợp nhiều chức năng trên cùng một nền tảng, vừa có giá trị thực tiễn, vừa phù hợp với định hướng của ngành Hệ thống thông tin.

### 13.2. Điểm mới của đề tài so với các website lyrics hiện nay là gì?

**Gợi ý trả lời:**

Điểm mới của đề tài không nằm ở việc chỉ hiển thị lời bài hát, mà ở sự kết hợp của nhiều lớp chức năng:

- tra cứu bài hát và nghệ sĩ,
- phân tích ý nghĩa bài hát bằng AI,
- gợi ý cá nhân hóa,
- recap,
- music persona,
- theo dõi nghệ sĩ,
- và phân hệ quản trị.

Nghĩa là hệ thống hướng đến trải nghiệm âm nhạc thông minh hơn, chứ không chỉ là kho lyrics.

### 13.3. Vì sao em chọn PHP + MySQL/MariaDB + XAMPP?

**Gợi ý trả lời:**

Em chọn bộ công nghệ này vì đây là stack phù hợp với phạm vi luận văn, dễ triển khai, ổn định, dễ kiểm thử và phù hợp với môi trường học thuật. PHP hỗ trợ tốt cho xử lý server-side, MySQL/MariaDB phù hợp cho lưu trữ dữ liệu quan hệ, còn XAMPP giúp cài đặt và chạy hệ thống thuận tiện trong môi trường cục bộ.

### 13.4. Kiến trúc 3 lớp của hệ thống thể hiện như thế nào?

**Gợi ý trả lời:**

Lớp giao diện là nơi người dùng tương tác qua trình duyệt.  
Lớp nghiệp vụ được viết bằng PHP, chịu trách nhiệm xử lý logic, xác thực, điều phối luồng dữ liệu và gọi API ngoài.  
Lớp dữ liệu dùng MySQL/MariaDB để lưu trữ dữ liệu hệ thống.

Cách tách này giúp hệ thống rõ trách nhiệm từng lớp, dễ bảo trì và mở rộng hơn.

### 13.5. Luồng dữ liệu tìm kiếm bài hát đi qua những bước nào?

**Gợi ý trả lời:**

Người dùng nhập từ khóa ở giao diện, request được gửi lên lớp nghiệp vụ, sau đó lớp nghiệp vụ truy vấn cơ sở dữ liệu để tìm bài hát hoặc nghệ sĩ phù hợp, rồi trả kết quả về giao diện. Nếu người dùng đã đăng nhập thì hệ thống đồng thời ghi lại lịch sử tìm kiếm để phục vụ thống kê và cá nhân hóa.

### 13.6. Dữ liệu nào được lưu trong hệ thống, dữ liệu nào lấy từ API ngoài?

**Gợi ý trả lời:**

Dữ liệu lõi như người dùng, bài hát, nghệ sĩ, album, tin tức, bình luận, theo dõi, thông báo, lịch sử tìm kiếm được lưu trong cơ sở dữ liệu MySQL/MariaDB.  
Dữ liệu ngoài chủ yếu là lyrics bổ sung, video YouTube và kết quả phân tích AI.  
Tức là hệ thống vẫn kiểm soát dữ liệu nghiệp vụ chính, còn API ngoài đóng vai trò hỗ trợ.

### 13.7. Vì sao recommendation không cần nhất thiết là một bảng khái niệm riêng trong CDM?

**Gợi ý trả lời:**

Recommendation trong hệ thống là dữ liệu suy diễn từ hành vi người dùng như lịch sử tìm kiếm, nghệ sĩ theo dõi và album yêu thích. Vì vậy, ở mức khái niệm, em xem nó là kết quả xử lý từ các thực thể gốc chứ không nhất thiết là một thực thể nghiệp vụ lõi. Tuy nhiên, ở mức triển khai hoặc mức luận lý, nếu cần lưu recommendation để tái sử dụng thì có thể bổ sung bảng riêng.

### 13.8. Recommendation hoạt động theo nguyên lý nào?

**Gợi ý trả lời:**

Hiện tại recommendation chủ yếu dựa trên dữ liệu hành vi và các luật nghiệp vụ cơ bản. Hệ thống phân tích lịch sử tìm kiếm, nghệ sĩ quan tâm, album yêu thích và một số tín hiệu tương tác để suy ra các bài hát phù hợp. Đây là cách tiếp cận đơn giản, dễ kiểm soát và phù hợp với phạm vi luận văn, dù chưa phải là recommendation bằng học máy nâng cao.

### 13.9. AI được dùng ở đâu và xử lý như thế nào?

**Gợi ý trả lời:**

AI được dùng chủ yếu trong chức năng phân tích ý nghĩa bài hát. Khi người dùng yêu cầu phân tích, hệ thống lấy lyrics của bài hát, gửi nội dung đó đến OpenAI API, nhận kết quả phân tích rồi hiển thị lại cho người dùng. Như vậy, AI đóng vai trò hỗ trợ diễn giải, còn hệ thống vẫn giữ quyền kiểm soát dữ liệu và luồng xử lý chính.

### 13.10. Vì sao em chọn OpenAI thay vì tự huấn luyện mô hình?

**Gợi ý trả lời:**

Trong phạm vi luận văn, việc sử dụng OpenAI API là phù hợp hơn vì giúp tiết kiệm chi phí huấn luyện, thời gian triển khai và tài nguyên tính toán. Mục tiêu của đề tài là tích hợp AI vào quy trình xử lý của hệ thống, chứ không phải nghiên cứu huấn luyện mô hình ngôn ngữ từ đầu.

### 13.11. Hạn chế lớn nhất của phần AI là gì?

**Gợi ý trả lời:**

Hạn chế lớn nhất là chất lượng kết quả phụ thuộc vào dữ liệu lyrics đầu vào và phản hồi từ dịch vụ AI. Ngoài ra, AI có thể chưa xử lý tối ưu với những bài hát có nhiều tầng nghĩa, ẩn dụ văn hóa hoặc ngữ cảnh quá đặc thù.

### 13.12. Recap và persona khác nhau thế nào?

**Gợi ý trả lời:**

Recap là phần tổng hợp lại hoạt động của người dùng theo thời gian, thiên về thống kê và tổng kết.  
Persona là phần diễn giải gu âm nhạc của người dùng, thiên về phân loại và mô tả phong cách nghe nhạc.  
Nói cách khác, recap trả lời câu hỏi “người dùng đã làm gì”, còn persona trả lời câu hỏi “người dùng có xu hướng nghe nhạc như thế nào”.

### 13.13. So với Spotify hoặc YouTube Music thì đề tài này khác gì?

**Gợi ý trả lời:**

Spotify hoặc YouTube Music là các hệ thống thương mại quy mô lớn, có recommendation rất mạnh và hệ sinh thái hoàn chỉnh. Đề tài của em không cạnh tranh trực tiếp về quy mô, mà tập trung vào:

- bài toán tra cứu lyrics và meaning,
- tích hợp AI để phân tích nội dung,
- cá nhân hóa ở mức phù hợp với luận văn,
- và mô hình hóa hệ thống theo góc nhìn phân tích thiết kế của ngành Hệ thống thông tin.

### 13.14. Vì sao trong recommendation em chưa dùng Machine Learning?

**Gợi ý trả lời:**

Vì trong phạm vi luận văn, em ưu tiên xây dựng một hệ thống hoàn chỉnh, ổn định và có thể kiểm thử được end-to-end. Recommendation theo luật và hành vi cơ bản giúp dễ triển khai, dễ giải thích và phù hợp với dữ liệu hiện có. Đây cũng là nền tảng để sau này nâng cấp lên Machine Learning.

### 13.15. Hệ thống lưu lịch sử tìm kiếm để làm gì?

**Gợi ý trả lời:**

Lịch sử tìm kiếm có 3 vai trò chính:

- hỗ trợ thống kê hành vi người dùng,
- làm đầu vào cho recommendation,
- và phục vụ recap/persona.

Đây là một trong những nguồn dữ liệu quan trọng nhất trong hệ thống.

### 13.16. Nếu API ngoài bị lỗi thì hệ thống xử lý thế nào?

**Gợi ý trả lời:**

Trong trường hợp API ngoài bị lỗi, hệ thống vẫn có thể tiếp tục hoạt động với dữ liệu lõi lưu trong cơ sở dữ liệu nội bộ. Chỉ các chức năng phụ thuộc trực tiếp vào API như phân tích AI, lyrics bổ sung hoặc video sẽ bị ảnh hưởng tạm thời. Điều này cho thấy hệ thống được thiết kế theo hướng dữ liệu nghiệp vụ chính không phụ thuộc hoàn toàn vào bên ngoài.

### 13.17. Nếu hội đồng hỏi vì sao có cả dữ liệu nội bộ lẫn dữ liệu ngoài?

**Gợi ý trả lời:**

Đó là vì hệ thống cần cân bằng giữa tính chủ động và tính mở rộng. Dữ liệu nội bộ giúp kiểm soát nghiệp vụ chính và đảm bảo tính nhất quán. Dữ liệu ngoài giúp bổ sung thông tin và mở rộng chức năng như lyrics, video, AI analysis. Cách kết hợp này phù hợp với đặc trưng của các hệ thống web tích hợp hiện nay.

### 13.18. Nếu được làm tiếp, em ưu tiên cải tiến phần nào?

**Gợi ý trả lời:**

Nếu được phát triển tiếp, em sẽ ưu tiên 3 hướng:

- nâng cấp recommendation bằng Machine Learning,
- cải thiện phần AI phân tích ý nghĩa bài hát,
- và phát triển dashboard quản trị cùng phiên bản mobile app.

### 13.19. Nếu hội đồng hỏi “điểm mạnh nhất của đề tài là gì?”

**Gợi ý trả lời:**

Điểm mạnh nhất của đề tài là đã xây dựng được một hệ thống web tương đối hoàn chỉnh, có sự kết hợp giữa:

- quản lý dữ liệu âm nhạc,
- tích hợp AI,
- cá nhân hóa,
- và mô hình quản trị dữ liệu.

Điều này cho thấy đề tài không chỉ dừng ở giao diện hay CRUD cơ bản, mà đã tiếp cận hệ thống theo hướng có chiều sâu hơn về dữ liệu và nghiệp vụ.

### 13.20. Nếu hội đồng hỏi “điểm yếu nhất của đề tài là gì?”

**Gợi ý trả lời:**

Điểm yếu lớn nhất là phần recommendation và AI mới dừng ở mức ứng dụng thực tiễn cơ bản, chưa được đánh giá sâu bằng các chỉ số học máy hoặc thí nghiệm quy mô lớn. Tuy nhiên, trong phạm vi luận văn tốt nghiệp, em ưu tiên hoàn thiện kiến trúc, luồng xử lý và hệ thống end-to-end trước, sau đó mới mở rộng theo hướng nâng cao.

## 14. Gợi ý chốt khi trả lời phản biện

Khi trả lời câu hỏi hội đồng, nên giữ nguyên tắc:

- trả lời ngắn trước, dài sau,
- nhấn mạnh **luồng dữ liệu** và **vai trò của từng lớp**,
- phân biệt rõ **dữ liệu gốc** và **dữ liệu suy diễn**,
- không nói AI là trung tâm hệ thống, mà nói AI là **thành phần hỗ trợ trong lớp nghiệp vụ**,
- và luôn nhấn mạnh đề tài mạnh ở việc **tích hợp các chức năng trên một nền tảng thống nhất**.
