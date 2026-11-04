<?php
declare(strict_types=1);

// Entry point: http://localhost/Project_DreamTeam/index.php
if (PHP_SAPI === 'cli-server') {
    $path = rawurldecode((string) (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/'));
    // Never allow direct HTTP access to internal PHP/SQL files.
    if (preg_match('~^/(Controller|Model|db)/|^/View/.*\\.php$~i', $path)) {
        http_response_code(403);
        exit('403 Forbidden');
    }
    if ($path !== '/' && $path !== '/index.php' && is_file(__DIR__ . $path)) {
        return false;
    }
}

// MVC folder mapping: Controller / Model / View / db, as in the team's project.
spl_autoload_register(static function (string $class): void {
    $mapping = [
        'App\\Controllers\\AuthController' => __DIR__ . '/Controller/AuthController.php',
        'App\\Controllers\\PhanCongController' => __DIR__ . '/Controller/PhanCongController.php',
        'App\\Core\\Security' => __DIR__ . '/Controller/Security.php',
        'App\\Core\\View' => __DIR__ . '/Controller/ViewRenderer.php',
        'App\\Core\\Database' => __DIR__ . '/Model/ketnoi.php',
        'App\\Models\\PhanCong' => __DIR__ . '/Model/PhanCong.php',
        'App\\Models\\User' => __DIR__ . '/Model/User.php',
        'App\\Services\\ExcelReader' => __DIR__ . '/Model/ExcelReader.php',
    ];
    if (isset($mapping[$class])) require $mapping[$class];
});

session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
    'path' => '/',
]);
session_start();
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

use App\Controllers\AuthController;
use App\Controllers\PhanCongController;
use App\Core\View;
use App\Core\Security;

$route = (string)($_GET['route'] ?? 'phancong');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$auth = new AuthController();

try {
    if ($route === 'login' && $method === 'GET') { $auth->loginPage(); exit; }
    if ($route === 'login' && $method === 'POST') { $auth->login(); exit; }
    if ($route === 'logout' && $method === 'POST') { $auth->logout(); exit; }

    if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'SO_GDDT') {
        View::redirect('login');
    }
    $controller = new PhanCongController();
    if ($route === 'phancong' && $method === 'GET') { $controller->index(); exit; }
    if ($route === 'save' && $method === 'POST') { Security::verify(); $controller->save(); exit; }
    if ($route === 'delete' && $method === 'POST') { Security::verify(); $controller->delete(); exit; }
    if ($route === 'import' && $method === 'GET') { $controller->importPage(); exit; }
    if ($route === 'import-preview' && $method === 'POST') { Security::verify(); $controller->importPreview(); exit; }
    if ($route === 'import-confirm' && $method === 'POST') { Security::verify(); $controller->importConfirm(); exit; }

    http_response_code(404);
    View::render('errors/notfound', ['pageTitle' => 'Không tìm thấy trang']);
} catch (\Throwable $error) {
    error_log('[UC21] ' . $error);
    http_response_code(500);
    View::render('errors/server', ['pageTitle' => 'Lỗi hệ thống']);
}
