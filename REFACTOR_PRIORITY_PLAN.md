# REFACTOR_PRIORITY_PLAN

Ke hoach nay chuyen hoa `AUDIT_PROJECT.md` thanh lo trinh refactor uu tien cao, muc tieu la sua cac diem gay vo he thong truoc, nhung van giu chuc nang dang chay.

## 1. Muc tieu

Refactor theo thu tu an toan, uu tien:

1. Giam rui ro logic he thong
2. Chot mot mo hinh du lieu thong nhat
3. Bo sung guard de sau do moi sua UI, performance, va feature

Khong lam ngay cac refactor "dep code" neu chua giai quyet xong:

- auth/password
- notifications
- route/file legacy
- schema `genre` / `cover_image`

## 1.1. Schema live da xac minh

Da xac minh truc tiep tu MySQL local `nln_lyrics` ngay 2026-03-25.

Bang hien co:

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

Diem quan trong da duoc chot:

- `song_genres` khong ton tai trong DB live
- `songs.genre_id` co ton tai va dang la schema that
- `songs.cover_image` va `albums.cover_image` cung ton tai
- `notifications` hien tai co schema:
  - `notification_id`
  - `user_id`
  - `news_id`
  - `artist_id`
  - `is_read`
  - `created_at`
- `admin/add_song.php` dang insert sai schema notifications

## 2. Nguyen tac thuc hien

- Khong doi ten cot DB truoc khi xac minh toan bo source.
- Moi pha phai co test tay toi thieu.
- Uu tien them lop compatibility truoc, roi moi loai bo legacy.
- Khong refactor dong thoi public va admin neu chua co checkpoint ro rang.
- Moi thay doi lien quan DB phai co rollback note.

## 3. Thu tu uu tien tong the

### Pha 0. Chot hien trang va khoa pham vi

Muc tieu:

- Xac minh schema DB that su truoc khi sua logic.
- Xac dinh file route nao dang duoc su dung that.

Cong viec:

1. Trich schema that su cua cac bang:
   - `users`
   - `songs`
   - `albums`
   - `artists`
   - `news`
   - `notifications`
   - `search_logs`
   - `genres`
   - `artist_follows`
   - `album_favorites`
   - `comments`
   - `song_genres` neu source van tham chieu
2. Doi chieu xem:
   - co `songs.cover_image` hay khong
   - `notifications` dang co nhung cot nao
   - `songs.genre_id` co phai schema chinh khong
   - `song_genres` con duoc dung that hay chi la phan du
3. Xac nhan route public:
   - `post.php` co phai trang bai hat chinh
   - `song.php` co con duoc link toi o dau khong

Deliverable:

- Bang mapping "schema thuc te vs source dang dung"
- Danh sach file legacy can dong bang

Trang thai hien tai:

- Pha nay da du thong tin de bat dau implementation

Checkpoint test:

- Public home, search, post, profile, artist, album, news van mo duoc
- Admin login, dashboard, songs, news, users van mo duoc

## 4. Pha 1. Hop nhat auth va password

Do uu tien: rat cao

Ly do:

- Dang co loi he thong: user tao tu admin co the khong dang nhap duoc ben public.
- Day la diem gay hong chuc nang co ban nhat.

Pham vi file:

- `public/login.php`
- `public/signup.php`
- `public/includes/change_password.php`
- `admin/add_user.php`
- co the them helper moi:
  - `config/auth_helpers.php` hoac `public/includes/auth_helpers.php`

Huong lam:

1. Chon chuan password moi:
   - `password_hash()`
   - `password_verify()`
2. Giữ compatibility voi user cu:
   - Neu password trong DB khop `sha256(input)` thi cho dang nhap
   - Sau dang nhap thanh cong, tu dong migrate hash sang `password_hash()`
3. Sua signup user moi de luu theo `password_hash()`
4. Sua change password user de luu theo `password_hash()`
5. Giu admin login support hash moi va hash cu trong giai doan chuyen doi

Khong lam trong pha nay:

- them rate limit
- them quyen/ACL moi
- doi UX login

Rui ro:

- Neu migrate hash sai, user se bi khoa tai khoan

Rollback:

- Truoc khi merge, backup bang `users`
- Neu co su co, quay lai code login cu va restore password column tu backup

Checkpoint test:

1. User cu dang nhap duoc
2. User cu sau khi dang nhap van dang nhap lai duoc
3. User moi dang ky xong dang nhap duoc
4. User tao tu admin dang nhap duoc ben public neu role la `user`
5. Admin login van hoat dong
6. Doi mat khau user va admin van hoat dong

Tieu chi hoan thanh:

- He thong chi con 1 chuan password chinh
- Hash cu chi con ton tai o muc compatibility, khong con duoc tao moi

## 5. Pha 2. Chot va hop nhat mo hinh notifications

Do uu tien: rat cao

Ly do:

- Notifications dang lech schema va lech nghiep vu giua public/admin.
- Hien tai notification "news moi" va "song moi" co kha nang khong chung mo hinh.

Pham vi file:

- `admin/add_news.php`
- `admin/add_song.php`
- `public/includes/navbar.php`
- `public/includes/api_mark_notification_read.php`
- `admin/index.php`
- neu can, tao helper/service:
  - `config/notification_helpers.php`

Can quyet dinh truoc:

1. Notification co phai luon gan voi `news_id` khong?
2. Notification cho song moi se:
   - tro den `post.php`
   - hay chi la tin noi bo?
3. Bang `notifications` se chot schema nao?

De xuat thuc dung:

- Chot `notifications` theo huong tong quat:
  - `notification_id`
  - `user_id`
  - `artist_id` nullable
  - `news_id` nullable
  - `song_id` nullable
  - `type`
  - `title`
  - `content`
  - `is_read`
  - `created_at`

Neu chua muon doi schema lon ngay:

- Tam thoi chon 1 use case an toan:
  - support notifications cho `news` truoc
  - tat hoac bo qua insert notification tu `add_song.php` vi code hien tai dang sai schema live

Thu tu thuc hien de xuat:

1. Xac minh schema DB that su
2. Chon contract response cho navbar user
3. Sua `admin/add_news.php` thanh nguon tao notification chuan
4. Sua `public/includes/navbar.php` va `api_mark_notification_read.php` theo contract moi
5. Sua `admin/index.php` dem unread dua tren schema moi
6. Sau cung moi quay lai `admin/add_song.php`

Rui ro:

- Sua schema notification se anh huong dashboard, navbar, mark-read

Rollback:

- Backup bang `notifications`
- Tach release dashboard va navbar theo tung buoc, khong gop mot lan

Checkpoint test:

1. Them news moi cho artist co follower
2. User follower thay notification tren navbar
3. Click notification mo dung trang
4. Mark read hoat dong
5. Dashboard admin dem unread khong loi SQL
6. User unfollow sau khi co notification van khong lam loi mark-read

Tieu chi hoan thanh:

- Chi con 1 mo hinh notification
- Khong con query join "vong" gay sai ownership

## 6. Pha 3. Dong bang route va file legacy

Do uu tien: cao

Ly do:

- `public/post.php` va `public/song.php` tung cung ton tai nhung khong dong nhat.
- Neu lap trinh vien sua nham `song.php` se rat de lam hong huong nghiep vu.

Pham vi file:

- `public/post.php`
- `public/song.php`
- tat ca file link den bai hat:
  - `public/index.php`
  - `public/search.php`
  - `public/artist.php`
  - `public/album.php`
  - `public/profile.php`
  - `public/includes/api_album_songs.php`
  - file admin neu co link public

Huong lam:

1. Chot `post.php` la route bai hat chinh
2. Danh dau `song.php` la legacy:
   - cach 1: redirect 301/302 sang `post.php?id=...` neu map duoc
   - cach 2: comment ro trong file va khong su dung nua
3. Tim va thay tat ca link cu neu con
4. Them note vao README/AUDIT neu can

Checkpoint test:

1. Tat ca link bai hat public deu vao dung `post.php`
2. `song.php` neu con truy cap thi khong gay loi fatals

Tieu chi hoan thanh:

- Chỉ con 1 route bai hat dang duoc support chinh thuc

Trang thai hien tai:

- `public/song.php` da duoc dong bang thanh compatibility redirect sang `public/post.php`
- Cac link public hien tai dang tro vao `post.php?id=...`

## 7. Pha 4. Chot schema `genre` va `cover_image`

Do uu tien: cao

Ly do:

- Day la 2 nhom cot dang gay lech query khap project.

### 4A. `genre`

Van de:

- Nhieu file dung `songs.genre_id`
- `admin/delete_song.php` lai xoa `song_genres`

Huong xu ly:

1. Xac minh schema that su
2. Neu project dang dung `songs.genre_id`:
   - bo logic `song_genres` khoi code nghiep vu
   - sua `delete_song.php`
3. Neu project that su dung many-to-many:
   - phai sua lai dashboard, CRUD songs, export, detail

De xuat:

- DB live da co `songs.genre_id` va khong co `song_genres`, nen giu `songs.genre_id` la schema chinh

### 4B. `cover_image`

Van de:

- Co noi lay cover tu `albums.cover_image`
- co noi goi `songs.cover_image`

Huong xu ly:

1. Chot quy tac:
   - cover chinh cua bai hat nam o dau
2. Neu bai hat trong album:
   - co fallback tu album khong?
3. Tao helper resolve cover:
   - `resolveSongCover(...)`

Pham vi file:

- `public/index.php`
- `public/post.php`
- `public/search.php`
- `public/artist.php`
- `public/album.php`
- `public/profile.php`
- `admin/songs.php`
- `admin/add_song.php`
- `public/includes/api_recommend_songs.php`

Checkpoint test:

1. Cover tren home/search/post/profile/admin songs hien dung
2. Bai hat khong co cover rieng van co fallback hop ly

## 8. Pha 5. Chuan hoa upload file va fallback asset

Do uu tien: trung binh cao

Pham vi file:

- `admin/add_artist.php`
- `admin/add_song.php`
- `admin/edit_song.php`
- `admin/add_news.php`
- `admin/profile.php`

Muc tieu:

- Validate extension
- Validate mime type
- Gioi han size
- Tao ten file an toan
- Kiem tra folder ton tai
- Khong ghi de file tuy tien

Nen tach helper:

- `config/upload_helpers.php`

Checkpoint test:

1. Upload jpg/png/webp hop le thanh cong
2. File gia mao extension bi chan
3. File qua lon bi chan

## 9. Pha 6. Giam rui ro XSS va output khong nhat quan

Do uu tien: trung binh

Pham vi file:

- `public/news.php`
- `public/post.php`
- `public/includes/api_get_comments.php`
- `public/includes/api_album_songs.php`
- cac file admin render title/image path

Muc tieu:

- Chot truong nao la plain text
- Chot truong nao cho phep rich text
- Escape dung ngu canh:
  - HTML text
  - attribute
  - JSON

Checkpoint test:

1. Comment khong inject duoc script
2. News content hien dung theo mong muon plain text hoac rich text

## 10. Pha 7. Chuan hoa query va tach helper dung chung

Do uu tien: trung binh

Chi lam sau khi 6 pha tren on dinh.

Muc tieu:

- Giam query truc tiep noi suy
- Tach helper:
  - auth
  - notification
  - upload
  - image fallback
  - cover resolve

Khong can dua thanh framework. Chi can giam duplicate va loi logic.

## 11. Backlog sau refactor uu tien cao

Chi nen lam sau khi xong cac pha tren:

- rate limit login
- CSRF token cho form/AJAX quan trong
- toi uu goi API lyrics/Gemini/YouTube
- toi uu `ORDER BY RAND()`
- encoding UTF-8 toan bo file
- pagination cho danh sach admin lon
- test automation co ban

## 12. De xuat cach trien khai theo dot

### Dot 1

- Pha 0
- Pha 1

Ket qua mong doi:

- Auth thong nhat, user/admin dang nhap on dinh

### Dot 2

- Pha 2
- Pha 3

Ket qua mong doi:

- Notifications ro rang
- Route bai hat khong con mo ho

### Dot 3

- Pha 4
- Pha 5

Ket qua mong doi:

- Schema dung duoc chot
- Upload va cover/fallback on dinh

### Dot 4

- Pha 6
- Pha 7

Ket qua mong doi:

- Source sach hon, an toan hon, de mo rong hon

## 13. Viec nen lam ngay trong lan code tiep theo

Neu bat dau implementation ngay, thu tu toi uu la:

1. Xac minh schema DB that su
2. Sua auth/password migration
3. Sua topbar admin session name mismatch
4. Dong bang `public/song.php`
5. Chot notification model

## 14. Dinh nghia hoan thanh

Ke hoach refactor uu tien cao duoc xem la hoan thanh khi:

- user va admin cung theo 1 chuan password chinh
- notifications co 1 schema va 1 contract ro rang
- khong con file route legacy gay nham
- `genre` va `cover_image` duoc chot theo 1 schema
- upload co validate co ban
- cac trang nghiep vu chinh van chay:
  - public: home, search, post, artist, album, profile, news
  - admin: login, dashboard, users, artists, songs, news, search_logs, export
