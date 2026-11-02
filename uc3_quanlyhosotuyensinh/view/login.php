<?php
// Đăng nhập được xử lý trong index.php trước khi xuất HTML.
$error = $loginError ?? '';
?>

<!-- Giao diện đăng nhập -->
<section class="panel narrow">

    <h1>Đăng nhập quản trị</h1>

    <!-- Thông báo lỗi đăng nhập -->
    <?php if ($error): ?>

        <div class="alert error">
            <?= h($error) ?>
        </div>

    <?php endif; ?>

    <!-- Biểu mẫu đăng nhập -->
    <form method="post">

        <!-- Mã bảo vệ CSRF -->
        <input
            type="hidden"
            name="csrf"
            value="<?= h(csrf()) ?>">

        <!-- Tên đăng nhập -->
        <label>
            Tên đăng nhập

            <input
                type="text"
                name="username"
                autocomplete="username"
                required>
        </label>

        <!-- Mật khẩu -->
        <label>
            Mật khẩu

            <input
                type="password"
                name="password"
                autocomplete="current-password"
                required>
        </label>

        <!-- Nút đăng nhập -->
        <button
            type="submit"
            class="btn primary">
            Đăng nhập
        </button>

    </form>

</section>