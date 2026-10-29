# ONIKI — Bất động sản & Giới thiệu việc làm tại Nhật Bản

Ứng dụng Laravel 12 (PHP 8.2+) có trang công khai tiếng Việt và tiếng Nhật, quản lý tin và tiếp nhận yêu cầu tư vấn.

## Chạy ứng dụng

Cài PHP, Composer và các extension Laravel yêu cầu (gồm pdo_sqlite hoặc pdo_mysql).
```sh
composer install
cp .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
php artisan app:create-admin
php artisan serve
```
PowerShell: dùng `Copy-Item .env.example .env` để tạo file môi trường.

- Trang Việt: http://localhost:8000/vi
- Trang Nhật: http://localhost:8000/ja
- Đăng nhập nội bộ: http://localhost:8000/login

Lệnh tạo quản trị viên hỏi tên/email và mật khẩu nhập ẩn. Không có tài khoản mặc định trong mã nguồn.
Giao diện nội bộ hiện dùng tiếng Việt; website công khai và nội dung tin dùng tiếng Việt–Nhật.

## Chức năng

- Trang chủ ONIKI, giới thiệu công ty, thông tin riêng tư và liên hệ tư vấn.
- Bất động sản: cho thuê / mua bán. Việc làm: toàn thời gian / bán thời gian.
- Tìm theo tiêu đề hoặc khu vực, lọc loại tin, phân trang và trang chi tiết Việt–Nhật.
- Tin có tiêu đề, mô tả, khu vực, giá/thu nhập bằng hai ngôn ngữ và ảnh đại diện JPG/PNG/WebP tối đa 5MB.
- Nhân viên chỉ soạn/sửa tin của mình; bản sửa của nhân viên chuyển về chờ duyệt. Quản trị viên xuất bản, ẩn và quản lý mọi tin.
- Tin nháp và ảnh tin nháp không công khai.
- Biểu mẫu tư vấn có đồng ý sử dụng thông tin, giới hạn gửi và trường chống bot; lưu vào database.
- **Yêu cầu tư vấn** chỉ dành cho quản trị viên, có trạng thái đã/chưa xử lý. Chưa gửi email tự động; quản trị viên xem yêu cầu trong hệ thống.
- Giữ quản lý nhân sự, giao việc, cập nhật tiến độ, khóa/mở tài khoản và đổi mật khẩu.
- CSRF, mật khẩu băm, tái tạo phiên đăng nhập, giới hạn đăng nhập sai và kiểm tra quyền ở máy chủ.

## Nội dung công ty

Chưa tự đặt địa chỉ, số điện thoại, email, giấy phép, đối tác hoặc tin đăng thật của ONIKI.
Điền thông tin đã được xác nhận trong môi trường:
```dotenv
ONIKI_EMAIL=
ONIKI_PHONE=
ONIKI_ADDRESS=
```
Quản trị viên vào **Tin đăng → Thêm tin**, nhập đủ hai ngôn ngữ, tải ảnh và chọn **Xuất bản công khai**.
Tất cả tin, thông tin pháp nhân, thông tin riêng tư và bản dịch cần được công ty duyệt trước khi sử dụng công khai.
Minh họa phố Nhật là SVG tự tạo, không phải ảnh của bất động sản đang chào thuê/bán.

## Kiểm thử

```sh
php artisan test
php artisan view:cache
```
14 tests / 128 assertions trên SQLite in-memory: phân quyền tài khoản và tin, hai ngôn ngữ, lọc tìm kiếm, ẩn bản nháp, tải ảnh và kiểm soát ảnh nháp, yêu cầu tư vấn.
Đã kiểm tra giao diện trình duyệt tại viewport 1440px và 390px, chuyển ngôn ngữ và gửi biểu mẫu tiếng Nhật. Chưa kiểm tra trên điện thoại vật lý.

## Triển khai

Đặt document root Apache/Nginx tại **public/**. Cấu hình HTTPS, APP_ENV=production, APP_DEBUG=false, SESSION_SECURE_COOKIE=true, APP_URL và database, rồi chạy migrate --force và optimize.
Ảnh nằm trong storage/app/private/listing-covers và chỉ được trả qua route kiểm tra quyền; không cần storage:link. Cấp quyền ghi cho storage và bootstrap/cache. Sao lưu cả database và ảnh.
Không commit .env, database, ảnh tải lên hoặc vendor. Dùng database session khi cần thu hồi ngay các phiên sau khi khóa nhân viên.

index.html là ảnh chụp HTML tĩnh của trang chủ Việt để xem hình thức; các đường dẫn danh mục, ngôn ngữ và biểu mẫu cần Laravel. GitHub Pages không thể chạy backend PHP. Không xem đăng lên GitHub là đã triển khai hosting.
