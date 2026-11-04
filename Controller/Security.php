<?php
declare(strict_types=1);

namespace App\Core;

final class Security
{
    public static function token(): string
    {
        if (!isset($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function verify(): void
    {
        if (
            !isset($_POST['_csrf']) || !is_string($_POST['_csrf']) ||
            !hash_equals(self::token(), $_POST['_csrf'])
        ) {
            http_response_code(419);
            exit('Phiên làm việc không hợp lệ. Vui lòng tải lại trang.');
        }
    }

    public static function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function intId(mixed $value): ?int
    {
        if (!is_scalar($value) || !preg_match('/^[1-9][0-9]*$/D', (string) $value)) {
            return null;
        }
        $id = filter_var($value, FILTER_VALIDATE_INT);
        return $id === false ? null : $id;
    }
}
