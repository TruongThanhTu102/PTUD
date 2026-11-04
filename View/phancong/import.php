<?php use App\Core\Security as S; ?>
<section class="hero">
       <div class="kicker">UC21 · HỘI ĐỒNG CHẤM THI</div>
       <h1>Nhập phân công từ Excel</h1>
       <p>Chọn kỳ tuyển sinh, tải file lên và kiểm tra trước khi lưu.</p>
</section>
<div class="tabbar"><a class="tab" href="<?= S::escape($baseUrl) ?>?route=phancong">Danh sách phân công</a><a
              class="tab active" href="<?= S::escape($baseUrl) ?>?route=import">Nhập từ Excel</a></div>
<section class="card">
       <div class="card-title">
              <h2>Tải file danh sách phân công</h2><a class="btn btn-light" download
                     href="<?= S::escape(rtrim(dirname($baseUrl), '/')) ?>/View/assets/mau_phan_cong.csv">Tải file
                     mẫu</a>
       </div>
       <?php if ($error): ?>
              <div class="alert alert-error" role="alert"><?= S::escape($error) ?></div><?php endif; ?>
       <p class="instructions">File .xlsx hoặc .csv gồm 6 cột: <strong>Mã PC, Mã kỳ, Mã giáo viên, Mã môn, Mã phòng,
                     Thao tác</strong>.</p>
       <form method="post" action="<?= S::escape($baseUrl) ?>?route=import-preview" enctype="multipart/form-data"
              class="form-grid">
              <input type="hidden" name="_csrf" value="<?= S::escape(S::token()) ?>">
              <label>Kỳ tuyển sinh<select name="year" required><?php foreach ($years as $y): ?>
                                   <option value="<?= (int) $y['maKyTuyenSinh'] ?>"
                                          <?= (int) $y['maKyTuyenSinh'] === (int) $year ? 'selected' : '' ?>>
                                          <?= S::escape($y['namHoc'] . ' (Mã kỳ: ' . $y['maKyTuyenSinh'] . ')') ?></option>
                            <?php endforeach; ?>
                     </select></label>
              <label>Chọn file Excel hoặc CSV<input type="file" name="excel" accept=".xlsx,.csv" required></label>
              <div class="form-buttons"><button class="btn btn-primary" type="submit">Kiểm tra dữ liệu</button></div>
       </form>
</section>
<?php if (is_array($preview) && count($preview['items'] ?? []) > 0): ?>
       <?php $errors = count(array_filter($preview['items'], static fn($x) => $x['error'] !== null)); ?>
       <section class="card">
              <div class="card-title">
                     <h2>Bảng xem trước</h2>
                     <div class="count-heading"><?= count($preview['items']) ?> dòng · <?= $errors ?> lỗi</div>
              </div>
              <div class="table-scroll">
                     <table>
                            <thead>
                                   <tr>
                                          <th>Dòng</th>
                                          <th>Giáo viên</th>
                                          <th>Môn</th>
                                          <th>Phòng</th>
                                          <th>Thao tác</th>
                                          <th>Kết quả</th>
                                   </tr>
                            </thead>
                            <tbody>
                                   <?php foreach ($preview['items'] as $item): ?>
                                          <tr>
                                                 <td><?= (int) $item['line'] ?></td>
                                                 <td><strong><?= S::escape($item['data']['maNhanSu']) ?></strong></td>
                                                 <td><?= (int) $item['data']['maMonThi'] ?></td>
                                                 <td><?= S::escape($item['data']['maPhongThi']) ?></td>
                                                 <td><strong><?= S::escape($item['action']) ?></strong></td>
                                                 <td><?php if ($item['error']): ?><span
                                                                      class="text-error"><?= S::escape($item['error']) ?></span><?php else: ?><span
                                                                      class="text-success">Hợp lệ</span><?php endif; ?></td>
                                          </tr>
                                   <?php endforeach; ?>
                            </tbody>
                     </table>
              </div>
              <?php if ($errors === 0): ?>
                     <form method="post" action="<?= S::escape($baseUrl) ?>?route=import-confirm" class="confirm-import">
                            <input type="hidden" name="_csrf" value="<?= S::escape(S::token()) ?>">
                            <input type="hidden" name="preview_token" value="<?= S::escape($preview['token']) ?>">
                            <button type="submit" class="btn btn-primary">✓ Xác nhận lưu</button>
                     </form>
              <?php else: ?>
                     <div class="alert alert-error">Sửa các dòng lỗi trong Excel rồi tải lại trước khi lưu.</div><?php endif; ?>
       </section>
<?php endif; ?>