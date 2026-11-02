
<?php

// Lớp kết nối cơ sở dữ liệu MySQL
class clsKetNoi
{
    // Mở kết nối
    public function moketnoi(): mysqli
    {
        return db();
    }

    // Đóng kết nối
    public function dongketnoi(mysqli $conn): bool
    {
        return $conn->close();
    }
}

// Kết nối database tuyển sinh lớp 10
function db(): mysqli
{
    $conn = new mysqli(
        'localhost',
        'root',
        '',
        'tuyensinh10'
    );

    // Kiểm tra kết nối
    if ($conn->connect_errno) {
        http_response_code(500);

        exit('Không thể kết nối đến cơ sở dữ liệu MySQL.');
    }

    // Thiết lập bảng mã tiếng Việt
    $conn->set_charset('utf8mb4');

    return $conn;
}

// Chuyển ký tự đặc biệt thành dạng an toàn khi hiển thị
function h($s): string
{
    return htmlspecialchars(
        (string)$s,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Chuyển hướng trang
function redirect($url): void
{
    header('Location: ' . $url);
    exit;
}

// Tạo mã CSRF bảo vệ biểu mẫu
function csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }

    return $_SESSION['csrf'];
}

// Kiểm tra mã CSRF khi gửi biểu mẫu
function verify_csrf(): void
{
    if (
        !hash_equals(
            $_SESSION['csrf'] ?? '',
            $_POST['csrf'] ?? ''
        )
    ) {
        http_response_code(403);

        exit('Phiên thao tác không hợp lệ.');
    }
}
