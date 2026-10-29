# ONIKI — Bất động sản & Giới thiệu việc làm tại Nhật Bản

Ứng dụng Laravel 12 (PHP 8.2+) có trang công khai tiếng Việt và tiếng Nhật, quản lý tin và tiếp nhận yêu cầu tư vấn.

Nhận diện sử dụng logo ONIKI GLOBAL do công ty cung cấp, xanh dương chủ đạo và xanh lá làm điểm nhấn. Logo gốc được giữ nguyên trong public/oniki-global-logo.png, hiển thị ở đầu trang, chân trang và khu nội bộ.

Phần việc làm dùng thêm logo ONIKI キャリアサポートセンター được cung cấp trong public/oniki-career-logo.png, trên mục dịch vụ, khu vực việc làm trang chủ, đầu trang tuyển dụng và trang chi tiết việc làm. Cả hai ảnh logo được giữ nguyên màu sắc và nội dung.

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
- Tìm bất động sản: http://localhost:8000/vi/find-home
- Bản đồ toàn màn hình: http://localhost:8000/vi/property-map
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

## Tìm nhà trên bản đồ

Trang bất động sản mặc định hiển thị danh sách cạnh bản đồ Leaflet / OpenStreetMap. Có bộ lọc thành phố, ngân sách (thuê: yên/tháng; mua: tổng giá bán), kiểu phòng và từ khóa; nút “Tìm trong khu vực này” áp dụng giới hạn vùng đang xem. Chuyển Việt–Nhật giữ các bộ lọc và vùng bản đồ. Có chế độ danh sách riêng, giao diện điện thoại và thông báo khi không tải được bản đồ.

Khi soạn tin bất động sản, chọn thành phố, giá dạng số, thông tin phòng và nhấp bản đồ để chọn tọa độ được phép công khai. Tin không có tọa độ vẫn hiện trong danh sách. Tin nháp và tin việc làm không xuất hiện trên bản đồ. Tối đa 500 điểm mới nhất phù hợp được hiển thị; danh sách có phân trang đầy đủ. Không có geocoding tự động hay nhập dữ liệu từ website BĐS khác.

Bản đồ nền cần kết nối Internet. Có thể cấu hình ONIKI_MAP_TILES cho nhà cung cấp tương thích; ghi nguồn theo điều khoản của nhà cung cấp. Chi tiết thư viện và nguồn hình ảnh: docs/design-notes.md.

## Kiểm chứng bản đồ và giao diện mới

```sh
php artisan test
php artisan view:cache
```
23 tests / 223 assertions trên SQLite in-memory: phân quyền tài khoản và tin, hai ngôn ngữ, lọc tìm kiếm, ẩn bản nháp, tải ảnh và kiểm soát ảnh nháp, yêu cầu tư vấn.
Đã kiểm tra giao diện trình duyệt tại viewport 1440px và 390px, chuyển ngôn ngữ và gửi biểu mẫu tiếng Nhật. Chưa kiểm tra trên điện thoại vật lý.

## Triển khai

Đặt document root Apache/Nginx tại **public/**. Cấu hình HTTPS, APP_ENV=production, APP_DEBUG=false, SESSION_SECURE_COOKIE=true, APP_URL và database, rồi chạy migrate --force và optimize.
Ảnh nằm trong storage/app/private/listing-covers và chỉ được trả qua route kiểm tra quyền; không cần storage:link. Cấp quyền ghi cho storage và bootstrap/cache. Sao lưu cả database và ảnh.
Không commit .env, database, ảnh tải lên hoặc vendor. Dùng database session khi cần thu hồi ngay các phiên sau khi khóa nhân viên.

index.html là ảnh chụp HTML tĩnh của trang chủ Việt để xem hình thức; các đường dẫn danh mục, ngôn ngữ và biểu mẫu cần Laravel. GitHub Pages không thể chạy backend PHP. Không xem đăng lên GitHub là đã triển khai hosting.

## Luồng tìm bất động sản theo mẫu tham khảo

Phần bất động sản lấy cảm hứng từ cách tổ chức tìm kiếm trên ongchuxomtro.com, với giao diện, logo và dữ liệu riêng của ONIKI:
- Chọn một trong 8 vùng tìm kiếm, rồi tìm theo tỉnh/quận, tuyến/ga hoặc bản đồ.
- Danh mục 47 tỉnh thành Nhật Bản; số tin theo tỉnh/quận/ga chỉ tính tin ONIKI đã xuất bản.
- Thông tin tuyến/ga lấy từ dữ liệu người đăng nhập, không có danh mục ga hay kho nhà từ website khác.
- Bản đồ toàn màn hình: cột Tỉnh thành / Quận / Điều kiện / Kết quả, cụm điểm nhà, popup và bộ lọc kết hợp.
- Bộ lọc loại nhà, nhiều kiểu phòng, giá, diện tích, tiện ích, tuổi nhà, khoảng cách đến ga và các nhóm nhu cầu.
- Khi đăng tin, bổ sung tỉnh, quận, tuyến/ga và các điều kiện đã xác nhận; dữ liệu chưa có không tự suy đoán.

Đã kiểm tra giao diện Việt–Nhật ở desktop 1440px và điện thoại giả lập 390px, chọn cụm bản đồ, danh sách, nhiều điều kiện và bảng lọc trên điện thoại. Dữ liệu thử có nhãn rõ ràng đã được dọn sau kiểm tra. Không có tin thật nếu công ty chưa nhập nội dung.

