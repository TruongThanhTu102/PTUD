<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Security;
use App\Core\View;
use App\Models\User;

final class AuthController
{
    public function loginPage(): void
    {
        if (isset($_SESSION['user'])) {
            View::redirect('phancong');
        }
        View::render('auth/login', ['pageTitle' => 'Đăng nhập', 'error' => '']);
    }

    public function login(): void
    {
        Security::verify();
        $name = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $user = strlen($name) <= 100 ? (new User())->findByUsername($name) : null;
        if (
            !$user || $user['trangThai'] !== 'Hoạt động' ||
            $user['vaiTro'] !== 'SO_GDDT' || !password_verify($password, $user['matKhauHash'])
        ) {
            View::render('auth/login', ['pageTitle' => 'Đăng nhập', 'error' => 'Tên đăng nhập hoặc mật khẩu không hợp lệ.']);
            return;
        }
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => $user['maTaiKhoan'], 'name' => $user['tenDangNhap'], 'role' => $user['vaiTro']];
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        View::redirect('phancong');
    }

    public function logout(): void
    {
        Security::verify();
        $_SESSION = [];
        session_regenerate_id(true);
        View::redirect('login');
    }
}
