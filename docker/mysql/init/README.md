# MySQL Init Folder

Nếu cần khởi tạo database khi chạy Docker lần đầu, hãy đặt file `.sql` hoặc `.sql.gz` vào thư mục này.

Ví dụ:

- `nln_lyrics.sql`

Khi chạy:

```bash
docker compose up --build
```

MySQL container sẽ tự import các file trong thư mục này nếu volume dữ liệu chưa được tạo trước đó.

Lưu ý:

- nếu `mysql_data` đã tồn tại, script init sẽ không chạy lại
- khi cần import lại từ đầu, xóa volume cũ rồi chạy lại:

```bash
docker compose down -v
docker compose up --build
```
