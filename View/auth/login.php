<?php use App\Core\Security as S; ?>
<section class="card login-card">
       <h1>Đăng nhập cán bộ Sở GD&amp;ĐT</h1>
       <p class="intro">Đăng nhập để quản lý hội đồng chấm thi.</p>
       <?php if ($error): ?>
              <div class="alert alert-error" role="alert"><?= S::escape($error) ?></div><?php endif; ?>
       <form method="post" action="<?= S::escape($baseUrl) ?>?route=login" class="form-grid single">
              <input type="hidden" name="_csrf" value="<?= S::escape(S::token()) ?>">
              <label>Tên đăng nhập<input name="username" required maxlength="100" autocomplete="username"
                            autofocus></label>
              <label>Mật khẩu<input name="password" type="password" required autocomplete="current-password"></label>
              <div class="button-row"><button type="submit" class="btn btn-primary">Đăng nhập</button></div>
       </form>
</section>