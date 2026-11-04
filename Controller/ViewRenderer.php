<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    /** @param array<string, mixed> $data */
    public static function render(string $view, array $data = []): void
    {
        $file = dirname(__DIR__) . '/View/' . $view . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('Missing view: ' . $view);
        }
        extract($data, EXTR_SKIP);
        $baseUrl = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require dirname(__DIR__) . '/View/layouts/header.php';
        require $file;
        require dirname(__DIR__) . '/View/layouts/footer.php';
    }

    public static function redirect(string $route, array $parameters = []): never
    {
        $url = ($_SERVER['SCRIPT_NAME'] ?? '/index.php') . '?' . http_build_query(['route' => $route] + $parameters);
        header('Location: ' . $url, true, 303);
        exit;
    }

    public static function flash(string $text, string $type = 'success'): void
    {
        $_SESSION['flash'] = ['text' => $text, 'type' => $type];
    }
}
