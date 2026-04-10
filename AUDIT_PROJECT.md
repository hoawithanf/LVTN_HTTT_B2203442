# AUDIT_PROJECT

Ban audit nay tap trung vao cac file PHP quan trong cua project `NLN_PROJECT`. Khong audit chi tiet `vendor/`, `libs/`, cac file HTML demo cua SB Admin 2, va cac asset tinh.

## 1. Tong ket nhanh

### Schema live da xac minh

Da doi chieu truc tiep tu MySQL local `nln_lyrics` ngay 2026-03-25.

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

### Kien truc hien tai

- Project la PHP thuan, include thu cong, khong co framework va khong co lop service/repository ro rang.
- Public va admin dung chung database `nln_lyrics` nhung tach thanh 2 file ket noi DB.
- Session user va session admin da tach tuong doi ro ve ten bien session.
- Nguon du lieu thong ke phu thuoc manh vao `search_logs`.

### Danh gia tong quan

- Muc do tiep quan: trung binh
- Muc do dong nhat source: thap den trung binh
- Muc do rui ro khi sua nhanh: cao

### Van de he thong noi bat

- Project dang co 2 chuan password hash song song:
  - user public: `hash('sha256', ...)`
  - admin: `password_hash()` va co ho tro fallback plaintext/hash cu
- Co dau hieu lech schema database giua cac file:
  - `songs.cover_image` va `albums.cover_image` cung ton tai
  - `notifications` schema live la `(notification_id, user_id, news_id, artist_id, is_read, created_at)`, nhung `admin/add_song.php` dang insert theo schema khac
  - `song_genres` khong ton tai trong schema live, nhung van bi xoa trong `admin/delete_song.php`
- Co file cu/khong dong nhat van ton tai va rat de gay nham:
  - `public/post.php` va `public/song.php`
- Nhieu file admin va API van query truc tiep bang noi suy bien da cast int, giam nguy co SQL injection nhung van khong dong deu voi chuan prepared statement.
- Upload file va ghi avatar/anh chua co validate mime type, extension, size.

## 2. Phan khong audit sau

- `admin/vendor/`
- `admin/libs/`
- cac file HTML demo nhu `admin/cards.html`, `admin/tables.html`, `admin/404.html`...
- file test thu nghiem neu khong nam tren luong chinh

## 3. Audit theo nhom file

## 3.1. Config va ket noi DB

### `config/database.php`

Vai tro:

- Ket noi DB cho admin va cac file dung chung.

Nhan xet:

- Dung `mysqli`, co `set_charset("utf8")`.
- Hardcode host/user/password/dbname.

Rui ro:

- Khong co env-based config.
- Neu doi thong tin DB phai sua thu cong.

Danh gia:

- On de van hanh local.
- Nen hop nhat voi `public/includes/database.php`.

### `public/includes/database.php`

Vai tro:

- Ket noi DB cho public.

Nhan xet:

- Cung dung `mysqli` toi `nln_lyrics`.
- Khong `set_charset()`.

Rui ro:

- Lech charset so voi `config/database.php`.
- Tao duplicate logic ket noi.

Danh gia:

- Nen dua ve mot bootstrap DB duy nhat.

### `config/config.php`

Vai tro:

- Placeholder cho API key.

Nhan xet:

- Dang define `MUSIXMATCH_API_KEY`, nhung source hien tai lai dung `.env` cho Gemini/YouTube.

Danh gia:

- File nay khong phai trung tam hien tai.
- De gay nham ve co che config.

## 3.2. Session va auth

### `public/includes/session.php`

Vai tro:

- `session_start()` neu chua mo.

Danh gia:

- Don gian, dung duoc.
- Khong co user guard helper.

### `admin/includes/admin_auth.php`

Vai tro:

- Chan truy cap admin neu thieu `admin_id` hoac `admin_role`.

Danh gia:

- Logic guard ro.
- Phu thuoc vao viec `admin/login.php` set dung session.

### `public/login.php`

Vai tro:

- Dang nhap user.

Nhan xet:

- So khop `username` + `password_hash`.
- Password duoc hash bang `sha256`.

Rui ro:

- Khong dung `password_hash()` / `password_verify()`.
- Khong co co che migrate hash nhu admin.
- Khong rate limit, khong CSRF.

Danh gia:

- Dang chay duoc neu DB da luu `sha256`.
- La diem can uu tien neu muon nang cap bao mat.

### `public/signup.php`

Vai tro:

- Tao tai khoan user.

Nhan xet:

- Kiem tra username/email trung.
- Password moi tiep tuc duoc luu bang `sha256`.

Rui ro:

- Khong co full_name luc tao.
- Khong validate password strength ngoai xac nhan.
- Tiep tuc khoa che chat he thong vao hash cu.

### `admin/login.php`

Vai tro:

- Dang nhap admin.

Nhan xet:

- Tim user co `role='admin'`.
- Ho tro 2 kieu xac thuc:
  - `password_verify`
  - fallback so sanh truc tiep plaintext/hash cu

Rui ro:

- `session_start()` truc tiep, khong qua wrapper.
- Cho phep fallback plaintext la huong migratory, nhung neu de lau se la no ky thuat.

Danh gia:

- Tot hon public login.
- Nen thong nhat chuan hash toan he thong.

### `public/includes/change_password.php`

Vai tro:

- Doi mat khau user qua AJAX.

Nhan xet:

- Xac thuc password hien tai bang `sha256`.
- Cap nhat password moi cung bang `sha256`.

Rui ro:

- Buoc chan viec chuyen doi user sang `password_hash`.
- Khong co CSRF.

### `admin/change_password.php`

Vai tro:

- Doi mat khau admin.

Nhan xet:

- Dung `password_verify()` va fallback old-style.
- Password moi duoc luu bang `password_hash()`.

Danh gia:

- Huong di dung.
- Khac biet ro voi user side.

## 3.3. Public entry points

### `public/index.php`

Vai tro:

- Trang chu.
- Hien top bai hat hot theo `search_logs` trong tuan va tin tuc moi.

Nhan xet:

- Query truc tiep, khong prepared statement.
- Dung `post.php?id=...` lam diem vao bai hat.

Rui ro:

- Phu thuoc vao cot:
  - `search_logs.search_time`
  - `albums.cover_image`
- Co the vo neu schema cover dang luu trong `songs`.

Danh gia:

- Day la entry point public chinh.

### `public/search.php`

Vai tro:

- Tim bai hat bang FULLTEXT va hien suggest qua AJAX.

Nhan xet:

- Dung `MATCH ... AGAINST` tren:
  - `songs.title`
  - `artists.artist_name`
  - `albums.album_name`
- Co phan pagination va highlight.

Rui ro:

- Can FULLTEXT index thuc te tren DB, neu khong se loi.
- Background `assets/img/search-bg.jpg` can xac minh co ton tai.

Danh gia:

- La file co gia tri nghiep vu cao.
- Logic kha ro rang.

### `public/post.php`

Vai tro:

- Chi tiet bai hat thuc te dang duoc source su dung nhieu nhat.
- Tu dong:
  - load bai hat
  - ghi `search_logs`
  - fetch lyrics neu DB rong
  - goi Gemini neu meaning rong
  - tim YouTube video va luu `youtube_video_id`
  - tai/comment/xoa comment

Phu thuoc:

- `songs`, `artists`, `albums`, `search_logs`, `comments`
- `includes/lyrics_api.php`
- `includes/env_loader.php`
- `includes/ajax_fetch_lyrics.php`
- `includes/ajax_analyze_lyrics.php`
- `includes/api_get_comments.php`
- `includes/api_add_comment.php`
- `includes/api_delete_comment.php`

Rui ro:

- File lam qua nhieu viec trong mot page controller.
- Goi API YouTube va update DB ngay trong request render page.
- Khong co timeout/fallback co cau truc cho API ngoai.
- Dieu kien xoa comment cho "admin" dang duoc xac dinh bang `user_id == 1`, khong theo `role`.
- Phu thuoc vao `.env` va network runtime.

Danh gia:

- La trung tam nghiep vu cua public.
- Can rat than trong khi sua.

### `public/song.php`

Vai tro:

- Route legacy da duoc dong bang.

Nhan xet:

- Hien chi con redirect an toan sang `public/post.php`.
- Khong con giu logic cu sai schema trong runtime.

Danh gia:

- Day la compatibility layer, khong phai noi de sua nghiep vu bai hat.

### `public/artist.php`

Vai tro:

- Trang chi tiet nghe si.
- Hien thong tin artist, top songs, album list, follow/unfollow, album preview modal.

Nhan xet:

- Artist info dung prepared statement.
- Top songs va albums lai noi suy `$artist_id` vao SQL sau khi cast int.
- Follow kiem tra bang `artist_follows`.

Rui ro:

- `api_album_songs.php` tra HTML truc tiep, khong escape title.
- File vua render UI, vua dieu khien modal data, vua xu ly social actions.

Danh gia:

- Day la file quan trong va dang dung duoc.

### `public/album.php`

Vai tro:

- Chi tiet album, danh sach bai hat, tong luot tim, favorite album.

Nhan xet:

- Dung prepared statement cho thong tin album, songs, tong views.
- Rating star moi la frontend-only, chua co save.

Rui ro:

- Phu thuoc vao `album_favorites.id`.
- Thong tin album co cot `description`, `release_year`.

Danh gia:

- Tuong doi on.
- Co dau hieu mo rong tiep duoc.

### `public/profile.php`

Vai tro:

- Dashboard ca nhan user.

Chuc nang:

- Thong tin user
- Lich su tim kiem co filter
- Artist da follow
- Album da favorite
- Recommendation tu lich su

Nhan xet:

- Nhieu phan dung prepared statement.
- Phan stats van query truc tiep voi `$user_id` noi suy.
- Co logic loai duplicate contiguous trong lich su tim kiem.

Rui ro:

- Phu thuoc vao nhieu bang mo rong:
  - `artist_follows`
  - `album_favorites`
  - `search_logs`
- Dung fallback anh `default-avatar.png`, can xac minh file ton tai.

Danh gia:

- Co gia tri nghiep vu cao.
- Can test ky neu doi schema search log.

### `public/news.php`

Vai tro:

- Chi tiet tin tuc.

Nhan xet:

- Dung prepared statement theo `news_id`.
- Render `<?= nl2br($news['content']) ?>` khong `htmlspecialchars`.

Rui ro:

- Neu admin luu HTML, day la chu y cho XSS/noi dung rich text.
- Neu chu truong la plain text thi can escape.

### `public/news_list.php`

Vai tro:

- Danh sach tin tuc, co loc theo `all` / `followed`.

Nhan xet:

- Filter `followed` dung `artist_follows`.
- Pagination ro rang.

Rui ro:

- Query `LIMIT/OFFSET` duoc noi suy truc tiep, nhung da cast int.
- Neu khong login, filter `followed` khong hoat dong.

### `public/charts.php`

Vai tro:

- Bang xep hang public theo `search_logs`.

Nhan xet:

- Filter `week/month/all`.
- `songs` query co ap dung `$timeWhere`.
- `artists` query lai khong ap dung `$timeWhere`.

Rui ro:

- UI cho phep doi range nhung top artists hien tai van all-time.

Danh gia:

- Loi logic nhe nhung ro rang.

## 3.4. Public includes va API

### `public/includes/navbar.php`

Vai tro:

- Navbar toan bo public.
- Load notifications cho user dang login.

Nhan xet:

- Query join `notifications -> news -> artist_follows`.
- Dung `n.artist_id`.

Rui ro:

- Mo hinh notifications phu thuoc vao cot `artist_id`.
- Goi `api_mark_notification_read.php` khong kem CSRF.

### `public/includes/api_search_suggest.php`

Vai tro:

- Suggest bai hat/nghe si cho `search.php`.

Nhan xet:

- Dung prepared statement, output JSON sach.

Danh gia:

- Tot va de bao tri.

### `public/includes/api_follow_artist.php`

Vai tro:

- Toggle follow artist.

Nhan xet:

- Dung prepared statement cho check/delete/insert.
- `SELECT id FROM artist_follows ...`

Rui ro:

- Gia dinh PK ten `id`.
- Khong header JSON.
- Khong dong statement.

Trang thai voi schema live:

- Phu hop voi DB hien tai vi `artist_follows` that su dung PK `id`.

### `public/includes/api_favorite_album.php`

Vai tro:

- Toggle favorite album.

Nhan xet:

- Dung prepared statement.
- Response JSON ro rang.

Danh gia:

- Kha on.

Trang thai voi schema live:

- Phu hop voi DB hien tai vi `album_favorites` that su dung PK `id`.

### `public/includes/api_get_comments.php`

Vai tro:

- Tai danh sach comment cho bai hat.

Nhan xet:

- Dung prepared statement.
- Tra JSON array comments.

Rui ro:

- Khong escape content o backend, frontend inject thang vao HTML.
- Co the bi stored XSS neu comment khong duoc sanitize luc render.

### `public/includes/api_add_comment.php`

Vai tro:

- Them comment.

Nhan xet:

- Dung prepared statement.

Rui ro:

- Khong validate do dai/noi dung.
- Khong co anti-spam/rate limit.

### `public/includes/api_delete_comment.php`

Vai tro:

- Xoa comment.

Nhan xet:

- Chi owner hoac `user_id == 1` moi xoa duoc.

Rui ro:

- Logic admin hardcode theo `user_id == 1`, khong theo `role`.

### `public/includes/api_mark_notification_read.php`

Vai tro:

- Danh dau thong bao da doc.

Nhan xet:

- Dung prepared statement update join `artist_follows`.

Rui ro:

- Logic ownership thong bao hoi vong:
  - thong bao da co `user_id`
  - nhung van xac thuc lai qua `artist_follows`
- Neu user unfollow sau khi nhan thong bao, kha nang khong mark duoc nua.

### `public/includes/api_album_songs.php`

Vai tro:

- Tra HTML danh sach bai hat trong album cho modal artist page.

Nhan xet:

- Query truc tiep bang `$album_id` noi suy sau cast int.
- Echo HTML truc tiep.

Rui ro:

- Khong escape title.
- Khong phai JSON API.

### `public/includes/api_recommend_songs.php`

Vai tro:

- Goi y bai hat theo lich su tim kiem.

Nhan xet:

- Co 4 nhom recommendation:
  - top_favorites
  - same_artist
  - same_album
  - collaborative
- Nhan `user_id` tu query string.

Rui ro:

- User co the doc recommendation cua user khac neu biet `user_id`.
- Query co tham chieu `s.cover_image` trong khi source khac nhieu noi dung `albums.cover_image`.
- Dung `ORDER BY RAND()` se ton chi phi khi data lon.
- Collaborative query dung SQL string dong cho `IN (...)`.

Danh gia:

- Vui ve ve y tuong, nhung can siet auth neu dung thuc te.

### `public/includes/ajax_fetch_lyrics.php`

Vai tro:

- Lay lyrics qua API va cache vao DB.

Nhan xet:

- Load `.env`.
- Goi `fetchLyricsAndCache`.
- Bat buoc `song_id`.

Rui ro:

- Dang `display_errors = 1`, co the lam lo thong tin trong moi truong production.

### `public/includes/ajax_analyze_lyrics.php`

Vai tro:

- Lay meaning tu DB hoac Gemini roi cache vao `songs.meaning`.

Nhan xet:

- Luong logic ro.

Rui ro:

- Moi request co the gay goi API ngoai neu meaning chua co.
- Chua co circuit breaker / retry policy / quota guard.

### `public/includes/meaning_api_gemini.php`

Vai tro:

- Goi Gemini API.

Nhan xet:

- Prompt dang hardcode theo persona fan Taylor Swift.

Rui ro:

- Khong phu hop cho bai hat cua nghe si khac.
- De gay quality issue ve noi dung phan tich.

### `public/includes/update_profile.php`

Vai tro:

- Cap nhat `full_name`, `email`.

Nhan xet:

- Dung prepared statement.
- Co validate email va duplicate.

Danh gia:

- Kha on.
- Khong co CSRF.

## 3.5. Admin dashboard va helper

### `admin/index.php`

Vai tro:

- Dashboard chinh cua admin.

Nhan xet:

- Co `safeCount()` check ton tai bang.
- Doc:
  - tong users, songs, artists
  - unread notifications
  - search activity 7 ngay
  - songs by genre
  - top songs
  - top artists
  - progress lyrics

Rui ro:

- Query truc tiep khap file.
- Gia dinh:
  - `search_logs.search_time`
  - `genres.genre_name`
  - `songs.genre_id`
  - `notifications.is_read`
- Card link toi `notifications.php` nhung file nay hien chua thay trong source chinh.

Danh gia:

- Day la file quan trong nhat ben admin.

### `admin/includes/topbar.php`

Vai tro:

- Topbar admin.

Nhan xet:

- Lay ten admin tu `$_SESSION['admin_name']`.

Rui ro:

- `admin/login.php` va `admin/profile.php` dang set `admin_username`, khong phai `admin_name`.
- Ket qua: topbar co the hien thi "Admin" thay vi ten that.

### `admin/profile.php`

Vai tro:

- Cap nhat thong tin admin va avatar.

Nhan xet:

- Dung base64 cropper upload avatar vao `admin/uploads/avatars/`.
- Update `users.avatar`.

Rui ro:

- Khong validate image bytes/type.
- Dung `mkdir(..., 0777, true)`.
- Path avatar hien thi truc tiep tu DB.

Danh gia:

- Huu dung, nhung can siet upload.

## 3.6. Admin CRUD

### `admin/users.php`

Vai tro:

- Liet ke user.

Nhan xet:

- Query truc tiep, khong pagination.

Danh gia:

- Don gian, de doc.

### `admin/add_user.php`

Vai tro:

- Tao user/admin.

Nhan xet:

- Dung `password_hash()`.

Rui ro:

- User duoc tao tu admin se co hash kieu moi.
- Public login lai chi hieu `sha256`.

He qua:

- User tao o admin co the khong dang nhap duoc o public.

Day la mot loi logic he thong muc cao.

### `admin/edit_user.php`

Vai tro:

- Sua `full_name`, `role`.

Nhan xet:

- Khong cho sua username/email.

Danh gia:

- Don gian va an toan tuong doi.

### `admin/delete_user.php`

Vai tro:

- Xoa user.

Nhan xet:

- Co chan xoa admin cuoi cung.
- Nhung dung query truc tiep:
  - dem admin
  - lay role
  - delete user

Rui ro:

- Khong kiem tra relation phu:
  - comments
  - search_logs
  - artist_follows
  - album_favorites
  - notifications

### `admin/artists.php`

Vai tro:

- Liet ke artists.

Nhan xet:

- Query truc tiep `SELECT *`.
- Anh fallback la `../public/assets/img/no-avatar.png`.

Rui ro:

- Can xac minh file fallback co ton tai.

### `admin/add_artist.php`

Vai tro:

- Them nghe si.

Nhan xet:

- Upload avatar bang ten file goc + timestamp.
- Dung prepared statement cho insert.

Rui ro:

- Khong validate file upload.
- `bio` co the la HTML do nhap qua CKEditor, nhung public `artist.php` lai `htmlspecialchars`, thanh ra rich text bi mat dinh dang.

### `admin/songs.php`

Vai tro:

- Liet ke va loc bai hat.

Nhan xet:

- Day la trang CRUD song tot nhat ve mat cau truc query.
- Dung prepared statement cho count va main query.

Rui ro:

- Fallback anh `../public/assets/img/no-img.jpg` can xac minh ton tai.

### `admin/add_song.php`

Vai tro:

- Them song.

Nhan xet:

- Cover image co the lay tu upload hoac copy tu album.
- Sau khi them song se insert notifications cho followers.

Rui ro:

- Insert notifications vao cac cot:
  - `user_id, title, content, created_at`
- Trong khi file khac coi notifications theo:
  - `news_id, artist_id, is_read`

Day la mot dau hieu lech schema rat manh.

He qua:

- Notification tu "song moi" co the khong hien dung trong navbar user.
- Tren schema live, lenh insert nay se sai truc tiep vi bang `notifications` khong co cot `title`, `content`.

### `admin/edit_song.php`

Vai tro:

- Sua song.

Nhan xet:

- Dung prepared statement cho update.
- Lyrics/meaning duoc sua tay.

Rui ro:

- Query albums theo `artist_id` dung query string noi suy.
- Upload cover chua validate.

### `admin/delete_song.php`

Vai tro:

- Xoa song.

Nhan xet:

- Xoa `song_genres` roi xoa `songs`.

Rui ro:

- Mo hinh hien tai chu yeu dung `songs.genre_id`, khong thay CRUD nao dung `song_genres`.
- Day la dau hieu schema cu van con sot.
- Khong xoa cascade:
  - `search_logs`
  - `comments`

Trang thai voi schema live:

- `song_genres` khong co trong DB hien tai, nen lenh xoa nay co nguy co loi SQL ngay khi xoa bai hat.

### `admin/song_details.php`

Vai tro:

- Xem chi tiet song.

Nhan xet:

- Dung prepared statement.
- Co preview YouTube neu co `youtube_video_id`.

Danh gia:

- Huu ich, logic ro.

### `admin/news.php`

Vai tro:

- Liet ke news.

Nhan xet:

- Don gian.

Rui ro:

- Image path echo truc tiep khong escape filename.

### `admin/add_news.php`

Vai tro:

- Tao news va notifications cho follower cua artist.

Nhan xet:

- Logic notification o day phu hop hon voi navbar user:
  - `notifications(user_id, news_id, artist_id)`
- Co anti-duplicate notification theo `(user_id, news_id)`.

Danh gia:

- La nguon notification hop ly nhat cua he thong hien tai.

### `admin/delete_news.php`

Vai tro:

- Xoa news va xoa notifications lien quan.

Nhan xet:

- Query truc tiep bang `news_id`.

Danh gia:

- Logic nghiep vu hop ly.

## 3.7. Admin thong ke, tim kiem, export

### `admin/search_logs.php`

Vai tro:

- Quan ly lich su tim kiem, filter, export CSV.

Nhan xet:

- Dung prepared statement kha deu.

Danh gia:

- Mot trong cac file admin sach hon.

### `admin/search_admin.php`

Vai tro:

- Tim nhanh across songs/artists/users/news.

Nhan xet:

- Dung prepared statement.
- Render ket qua rat basic.

Danh gia:

- Dung duoc cho admin support.

### `admin/export_report.php`

Vai tro:

- Xuat CSV/PDF.

Nhan xet:

- Query users, artists, songs, top songs.
- Co query `searchLogs` nhung khong dung trong output PDF/CSV hien tai.

Rui ro:

- HTML PDF dang noi suy du lieu truc tiep, khong escape.
- Neu noi dung co ky tu dac biet/HTML thi PDF co the loi format.
- Dau hieu logic "searchLogs load nhung khong xuat".

### `admin/ajax_get_albums.php`

Vai tro:

- Tra list album theo artist cho form them/sua song.

Nhan xet:

- API gon, dung prepared statement.

Danh gia:

- Tot.

## 4. Ma tran van de uu tien

### Muc cao

1. Khong dong nhat co che password giua public va admin.
   - `public/login.php`
   - `public/signup.php`
   - `public/includes/change_password.php`
   - `admin/add_user.php`

2. Lech schema notifications.
   - `admin/add_song.php`
   - `admin/add_news.php`
   - `public/includes/navbar.php`
   - `public/includes/api_mark_notification_read.php`
   - `admin/index.php`

3. Route legacy `public/song.php` da duoc dong bang; moi phat trien bai hat phai di qua `public/post.php`.

4. Lech schema genre.
   - `songs.genre_id`
   - `song_genres` trong `admin/delete_song.php`, trong khi bang nay khong ton tai trong DB live

### Muc trung binh

1. Upload file khong validate.
   - `admin/add_artist.php`
   - `admin/add_song.php`
   - `admin/edit_song.php`
   - `admin/add_news.php`
   - `admin/profile.php`

2. Comment va rich content co nguy co XSS.
   - `public/includes/api_get_comments.php`
   - `public/news.php`
   - `public/post.php`

3. Notification ownership logic chua that su ben vung.
   - `public/includes/api_mark_notification_read.php`

4. Topbar admin dung sai ten session field.
   - `admin/includes/topbar.php`

### Muc thap

1. Encoding tieng Viet dang loi o nhieu file.
2. Query style khong dong deu.
3. Mot so file/fallback image co the khong ton tai.
4. Co nhieu file demo/legacy lam nhiu bo source.

## 5. Thu tu de xuat khi tiep tuc sua project

1. Chot schema thuc te cua DB.
   - xac minh bang/cot `notifications`
   - xac minh `songs.cover_image` va `albums.cover_image`
   - xac minh `genre_id` vs `song_genres`

2. Chot file route thuc te.
   - giu `public/post.php`
   - giu `public/song.php` chi nhu compatibility redirect

3. Hop nhat auth/password.
   - chon `password_hash()` cho ca user/admin
   - them migration logic cho user public

4. Hop nhat notification model.

5. Boc tach logic upload file va validate extension/mime.

6. Sau do moi toi uu UI va thong ke.

## 6. Danh sach file PHP quan trong nhat can nho

Public:

- `public/index.php`
- `public/search.php`
- `public/post.php`
- `public/artist.php`
- `public/album.php`
- `public/profile.php`
- `public/news.php`
- `public/news_list.php`
- `public/login.php`
- `public/signup.php`
- `public/includes/navbar.php`
- `public/includes/ajax_fetch_lyrics.php`
- `public/includes/ajax_analyze_lyrics.php`

Admin:

- `admin/index.php`
- `admin/login.php`
- `admin/users.php`
- `admin/add_user.php`
- `admin/songs.php`
- `admin/add_song.php`
- `admin/edit_song.php`
- `admin/news.php`
- `admin/add_news.php`
- `admin/search_logs.php`
- `admin/export_report.php`
- `admin/profile.php`
- `admin/includes/admin_auth.php`

## 7. Ket luan

Project dang co nghiep vu ro rang va da chay duoc tren local, nhung mang dau vet cua nhieu dot phat trien noi tiep. Dieu nay khong lam project "hong", nhung lam cho moi thay doi logic can rat co ky luat.

Neu can tiep tuc phat trien an toan, uu tien khong phai la them tinh nang moi, ma la:

- chot schema DB that su,
- hop nhat auth va notifications,
- danh dau file legacy,
- va dat lai mot so chuan co ban cho upload, session va API.
