# Dự án 1
Cổng quản lý công việc nội bộ bằng Laravel 12, PHP 8.2+, SQLite hoặc MySQL. Giao diện tiếng Việt responsive, CSS thuần, không cần build Node.

## Chạy ứng dụng
Cần PHP 8.2+, Composer, các extension Laravel yêu cầu và pdo_sqlite (hoặc pdo_mysql).
```sh
composer install
cp .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
php artisan app:create-admin
php artisan serve
```
Trên PowerShell dùng `Copy-Item .env.example .env` thay cho cp. Mở http://localhost:8000.
Lệnh tạo admin hỏi tên/email và nhập mật khẩu ẩn; không có tài khoản hoặc mật khẩu mặc định.
Quản trị viên đăng nhập, vào **Nhân sự** để tạo nhân viên, rồi vào **Công việc** để giao nhiệm vụ.

## Chức năng
- Trang giới thiệu, đăng nhập, đăng xuất và đổi mật khẩu.
- Admin tạo/khóa/mở khóa nhân viên; giao/xóa công việc; xem toàn bộ tiến độ.
- Nhân viên chỉ xem và cập nhật trạng thái nhiệm vụ được giao.
- Tìm tên công việc, lọc trạng thái, phân trang; mô tả và hạn hoàn thành.
- Kiểm tra quyền ở máy chủ, CSRF, mật khẩu băm, tái tạo phiên đăng nhập, giới hạn 5 lần đăng nhập sai/phút cho mỗi email/IP.
- Khóa nhân viên thu hồi phiên đăng nhập lưu trong database.

## Kiểm thử
```sh
php artisan test
php artisan view:cache
```
Bộ kiểm thử dùng SQLite in-memory, không tác động database thật.
Đã kiểm tra 8 tests / 62 assertions trên PHP 8.2.12. Chưa kiểm tra trực quan bằng trình duyệt trên thiết bị thật.

## Triển khai
Đặt document root của Apache/Nginx tại **public/**, không phải gốc repository.
`index.html` ở gốc là bản giới thiệu tĩnh theo yêu cầu ban đầu; đăng nhập và dữ liệu chỉ hoạt động khi chạy Laravel. GitHub Pages không chạy được backend PHP.
Đặt APP_ENV=production, APP_DEBUG=false, APP_URL theo tên miền HTTPS, SESSION_SECURE_COOKIE=true, cấu hình database, chạy migrate --force và optimize. Chỉ storage/ và bootstrap/cache/ cần quyền ghi.
Không commit .env, database, vendor hoặc log. Sao lưu database định kỳ.

## Phạm vi nội dung
Repo gốc chưa có nghiệp vụ sản phẩm cụ thể. Phiên bản này dùng tên **Dự án 1** và quy trình quản lý công việc nội bộ làm nền tảng; không chứa số liệu hoặc khách hàng hư cấu.

