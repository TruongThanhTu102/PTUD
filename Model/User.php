<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    public function findByUsername(string $name): ?array
    {
        $stmt = Database::connection()->prepare('SELECT maTaiKhoan, tenDangNhap, matKhauHash, vaiTro, trangThai FROM TaiKhoan WHERE tenDangNhap = ? LIMIT 1');
        $stmt->execute([$name]);
        return $stmt->fetch() ?: null;
    }
}
