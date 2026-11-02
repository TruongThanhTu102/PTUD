<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Hệ thống tuyển sinh lớp 10</title>

    <link rel="stylesheet" href="assets/style.css?v=11">
</head>

<body>

    <!-- Thanh tiêu đề -->
    <header class="top">

        <!-- Logo và tên hệ thống -->
        <div class="brand">

            <span class="brand-icon">🎓</span>

            <span>
                <strong>HỆ THỐNG TUYỂN SINH LỚP 10</strong>

                <small>Quản lý hồ sơ tuyển sinh</small>
            </span>

        </div>

        <!-- Nút mở menu trên thiết bị di động -->
        <button
            class="menu"
            type="button"
            aria-label="Mở menu"
            onclick="document.querySelector('.navmenu').classList.toggle('show')">
            ☰
        </button>

        <!-- Điều hướng chỉ dành cho UC03 -->
        <nav class="navmenu">
            <?php if (!empty($_SESSION['admin'])): ?>
                <a href="index.php?page=hosos">Quản lý hồ sơ</a>
                <a href="logout.php">Đăng xuất</a>
            <?php else: ?>
                <a href="index.php?page=login">Đăng nhập</a>
            <?php endif; ?>
        </nav>

    </header>

    <!-- Nội dung chính -->
    <main class="container">
