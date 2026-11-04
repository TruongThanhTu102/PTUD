# Project_DreamTeam — UC21 Quản lý hội đồng chấm thi

## Cấu trúc đúng theo dự án nhóm

```text
Project_DreamTeam/
├── Controller/
│   ├── AuthController.php             # Đăng nhập, đăng xuất
│   ├── PhanCongController.php         # CRUD + nhập Excel
│   ├── Security.php                   # CSRF, lọc ID, chống XSS
│   └── ViewRenderer.php               # Nạp file trong View/
├── db/
│   └── tuyen_sinh_lop10.sql             # Khởi tạo CSDL dùng chung và dữ liệu mẫu UC21
├── Model/
│   ├── ketnoi.php                    # PDO MySQL (đổi DB tại đây)
│   ├── PhanCong.php                  # Truy vấn, kiểm tra trùng & chuyên môn
│   ├── User.php                      # Tài khoản
│   └── ExcelReader.php               # Đọc .xlsx/.csv
├── View/
│   ├── auth/login.php
│   ├── phancong/index.php            # Giao diện CRUD
│   ├── phancong/import.php           # Giao diện Excel
│   ├── layouts/header.php, footer.php
│   ├── errors/
│   └── assets/css/style.css, js/app.js, mau_phan_cong.csv
├── index.php                         # Entry point và định tuyến
└── README.md
```

**MVC:** `index.php` định tuyến → `Controller` xử lý yêu cầu → `Model` truy vấn PDO → `View` hiển thị. CSS, JavaScript và HTML/PHP giao diện chỉ ở `View`.

## Chạy trên XAMPP + VS Code

1. Cài XAMPP với PHP 8.1+ và MySQL/MariaDB; bật **Apache**, **MySQL**.
2. Giải nén sao cho thư mục là **`C:\xampp\htdocs\Project_DreamTeam`**.
3. Mở VS Code tại thư mục `Project_DreamTeam`.
4. Vào **http://localhost/phpmyadmin** và import file **`db/tuyen_sinh_lop10.sql`** vào MySQL (script tự tạo database `tuyen_sinh_lop10`). Chỉ làm trên database trống để tránh xung đột với các UC khác.
5. Cấu hình tập trung ở **`Model/ketnoi.php`** (mặc định `DB_NAME=tuyen_sinh_lop10`). Nếu MySQL có mật khẩu / cổng khác, sửa file hoặc đặt biến môi trường `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`.
6. Mở **http://localhost/Project_DreamTeam/index.php?route=login**.
7. Đăng nhập tài khoản *demo*: **so.gddt** / **123456**. Đây chỉ là tài khoản thử nghiệm, cần thay trước khi triển khai thật.

## Chức năng

- Thêm: chọn kỳ tuyển sinh, giáo viên, môn và phòng rồi bấm **Lưu phân công**. Nếu lưu thành công hệ thống chuyển về danh sách, bỏ lọc cũ và đánh dấu dòng vừa thêm.
- Xem: đọc thông tin phân công.
- Sửa: đổi môn/phòng được chấm; theo đặc tả, không đổi giáo viên/kỳ ở chế độ Sửa.
- Xóa: có hộp thoại xác nhận; không xóa khi dữ liệu khác tham chiếu (ràng buộc SQL).
- Tìm kiếm và lọc theo giáo viên, mã PC, môn, kỳ.
- **Nhập Excel**: tải file `.xlsx` (bật PHP `zip` và `simplexml`) hoặc `.csv` → xem trước từng dòng → sửa file nếu có lỗi → xác nhận lưu một giao dịch; nếu lỗi sẽ rollback.
- Quy tắc **không trùng trong cùng kỳ + giáo viên + môn + phòng**. Nguyễn Văn An được chấm Toán/P102 nếu đã chấm Toán/P101, nhưng không được thêm Toán/P101 lần nữa trong cùng kỳ. Phân công khác môn phải phù hợp chuyên môn nhân sự.

## Mẫu CSV / Excel

Tải mẫu ngay trong trang **Nhập từ Excel**, hoặc mở file `View/assets/mau_phan_cong.csv`.
Các cột theo thứ tự **maPhanCong, maKyTuyenSinh, maNhanSu, maMonThi, maPhongThi, ThaoTac**.
Thao tác `THEM`: mã phân công để trống; `CAPNHAT`/`XOA`: phải có mã phân công cũ. Dùng ID kỳ (`1` là 2027-2028), mã môn (`1` Toán, `2` Ngữ văn, `3` Tiếng Anh), mã giáo viên `GV001`...`GV005`.

## Kết nối vào project chung của nhóm

**Tên cơ sở dữ liệu chung của toàn dự án:** `tuyen_sinh_lop10` (không gắn tên UC). Tất cả UC hiện tại và trong tương lai đều sử dụng database này. Đường dẫn kết nối tập trung tại `Model/ketnoi.php`.

**Lưu ý về phạm vi:** Tập tin SQL hiện chỉ khai báo **các bảng và dữ liệu mẫu cần cho UC21**, chưa phải thiết kế SQL hoàn chỉnh cho toàn bộ 24 UC trong báo cáo. Khi làm các UC tiếp theo, hãy thêm bảng/khóa ngoại vào cùng database qua script SQL bổ sung, **không tạo database mới cho từng UC**.

Nếu nhóm **đã có database hoặc bảng cùng tên**, **sao lưu và đối chiếu schema trước khi import**. `CREATE TABLE IF NOT EXISTS` sẽ không tự đồng bộ cấu trúc các bảng cũ và `INSERT IGNORE` có thể bỏ qua dữ liệu mẫu trùng khóa. Không import đè mà chưa xác nhận tính tương thích.

Mục 2.7 báo cáo thiếu cột `maPhongThi` trong `PhanCong`; phiên bản UC21 bổ sung cột này và dùng `maNhanSu` liên kết trực tiếp với `NhanSu`. Cần thống nhất các khóa/quan hệ với các thành viên khác khi hợp nhất thiết kế.

## Bảo mật, kiểm thử

- Form có CSRF token, truy vấn dùng `PDO::prepare()`, password dùng `password_verify`, kiểm tra vai trò sở GD&ĐT, lọc và escape dữ liệu hiển thị.
- Chặn truy cập trực tiếp vào các folder PHP/SQL qua Apache `.htaccess`. **Không** cấu hình Apache bỏ qua `.htaccess` hoặc cung cấp thư mục db/ công khai.
- Kiểm thử đã thực hiện khi đóng gói: lint cú pháp PHP, đọc CSV mẫu, smoke test trang đăng nhập + asset CSS. Chưa kiểm thử kết nối MySQL trong môi trường đóng gói; phải import database vào XAMPP và thử các thao tác thực tế.

## Khắc phục lỗi thường gặp

- **Trang trắng / 500:** bật `display_errors` trong XAMPP để xem nguyên nhân, kiểm tra PHP >= 8.1; đọc Apache error log.
- **could not find driver:** bật extension `pdo_mysql` trong `php.ini` rồi khởi động lại Apache.
- **Sai tài khoản MySQL:** chỉnh `Model/ketnoi.php`.
- **Không upload được .xlsx:** bật `extension=zip` và `extension=simplexml` trong `php.ini`; thử lại hoặc dùng CSV.
- **403 khi mở file PHP trong View:** bình thường; truy cập website qua `index.php`, không mở trực tiếp file View.
