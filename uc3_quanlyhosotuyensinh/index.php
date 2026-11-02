<?php
// Khởi tạo phiên đăng nhập
session_start();

// Nạp kết nối cơ sở dữ liệu và các hàm hỗ trợ
require_once __DIR__ . '/model/ketnoi.php';

// Ứng dụng demo duy nhất UC03: mặc định mở quản lý hồ sơ
$page = $_GET['page'] ?? 'hosos';
$validPages = ['login', 'hosos', 'edit'];

if (!in_array($page, $validPages, true)) {
    $page = 'hosos';
}

// Chưa đăng nhập thì phải đăng nhập trước khi xem/sửa hồ sơ
if (in_array($page, ['hosos', 'edit'], true) && empty($_SESSION['admin'])) {
    redirect('index.php?page=login');
}

// Xử lý POST đăng nhập trước khi xuất HTML để chuyển trang không lỗi header
if ($page === 'login' && !empty($_SESSION['admin'])) {
    redirect('index.php?page=hosos');
}

if ($page === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === 'admin' && hash_equals('Admin@123', $password)) {
        session_regenerate_id(true);
        $_SESSION['admin'] = 'admin';
        redirect('index.php?page=hosos');
    }

    $loginError = 'Tài khoản hoặc mật khẩu không đúng.';
}

include_once __DIR__ . '/view/header.php';

switch ($page) {
    case 'login':
        include __DIR__ . '/view/login.php';
        break;

    case 'edit':
        include __DIR__ . '/view/edit.php';
        break;

    case 'hosos':
    default:
        include __DIR__ . '/view/hosos.php';
        break;
}

include_once __DIR__ . '/view/footer.php';
