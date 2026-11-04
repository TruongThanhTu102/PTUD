<?php use App\Core\Security as S; ?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= S::escape($pageTitle ?? 'Hệ thống tuyển sinh lớp 10') ?> | Hệ thống tuyển sinh</title>
  <link rel="stylesheet" href="<?= S::escape(rtrim(dirname($baseUrl), '/')) ?>/View/assets/css/style.css">
  <script defer src="<?= S::escape(rtrim(dirname($baseUrl), '/')) ?>/View/assets/js/app.js"></script>
</head>

<body>
  <header class="header">
    <div class="header-inner">
      <div class="brand-logo" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.8"
          stroke-linecap="round" stroke-linejoin="round">
          <path d="m2 9 10-5 10 5-10 5L2 9Z" />
          <path d="M6 11v5c4 4 8 4 12 0v-5" />
          <path d="M22 9v7" />
        </svg>
      </div>
      <div class="brand-text"><strong>HỆ THỐNG TUYỂN SINH LỚP 10</strong><span>Cổng thông tin tuyển sinh trực
          tuyến</span></div>
      <div class="header-user">
        <?php if (isset($_SESSION['user'])): ?>
          <span>Sở GD&amp;ĐT</span>
          <form method="post" action="<?= S::escape($baseUrl) ?>?route=logout" class="inline-form"><input type="hidden"
              name="_csrf" value="<?= S::escape(S::token()) ?>"><button type="submit" class="logout">Đăng xuất</button>
          </form>
        <?php else: ?><span>Đăng nhập</span><?php endif; ?>
      </div>
    </div>
  </header>
  <main class="container">
    <nav class="breadcrumb"><a href="<?= S::escape($baseUrl) ?>?route=phancong">Trang chủ</a> <span>/</span> Quản lý
      tuyển sinh <span>/</span> Hội đồng chấm thi</nav>
    <?php if (is_array($flash ?? null)): ?>
      <div class="alert <?= ($flash['type'] ?? '') === 'error' ? 'alert-error' : 'alert-success' ?>" role="status">
        <?= S::escape($flash['text'] ?? '') ?></div>
    <?php endif; ?>