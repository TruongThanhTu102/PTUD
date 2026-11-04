<?php
use App\Core\Security as S;
$yrLabel = 'Chưa có kỳ tuyển sinh';
foreach ($years as $y)
  if ((int) $y['maKyTuyenSinh'] === $year)
    $yrLabel = $y['namHoc'];
$uniqueTeachers = count(array_unique(array_column($totalRows, 'maNhanSu')));
$uniqueSubjects = count(array_unique(array_column($totalRows, 'maMonThi')));
$uniqueRooms = count(array_unique(array_column($totalRows, 'maPhongThi')));
$saved = S::intId($_GET['saved'] ?? null);
$editYearLabel = $yrLabel;
if ($formMode === 'edit' && $old) {
  foreach ($years as $y)
    if ((int) $y['maKyTuyenSinh'] === (int) $old['maKyTuyenSinh'])
      $editYearLabel = $y['namHoc'];
}
?>
<section class="hero">
  <div class="kicker">NĂM HỌC <?= S::escape(str_replace('-', ' – ', $yrLabel)) ?></div>
  <h1>Quản lý hội đồng chấm thi</h1>
  <p>Quản lý giáo viên, môn chấm và phòng thi được phân công.</p>
</section>
<div class="metrics">
  <article class="metric"><strong><?= count($totalRows) ?></strong><span>Phân công</span></article>
  <article class="metric"><strong><?= $uniqueTeachers ?></strong><span>Giáo viên</span></article>
  <article class="metric"><strong><?= $uniqueSubjects ?></strong><span>Môn thi</span></article>
  <article class="metric"><strong><?= $uniqueRooms ?></strong><span>Phòng thi</span></article>
</div>
<div class="tabbar"><a class="tab active" href="<?= S::escape($baseUrl) ?>?route=phancong&amp;year=<?= $year ?>">Danh
    sách phân công</a><a class="tab" href="<?= S::escape($baseUrl) ?>?route=import&amp;year=<?= $year ?>">Nhập từ
    Excel</a></div>

<?php if ($formMode !== null): ?>
  <section class="card" id="form-panel">
    <div class="card-title">
      <h2>
        <?= $formMode === 'create' ? 'Thêm phân công chấm thi' : 'Sửa phân công PC' . str_pad((string) $old['maPhanCong'], 4, '0', STR_PAD_LEFT) ?>
      </h2><a class="close" title="Đóng" href="<?= S::escape($baseUrl) ?>?route=phancong&amp;year=<?= $year ?>">×</a>
    </div>
    <?php if ($formError): ?>
      <div class="alert alert-error" role="alert"><?= S::escape($formError) ?></div><?php endif; ?>
    <form action="<?= S::escape($baseUrl) ?>?route=save" method="post" class="form-grid">
      <input type="hidden" name="_csrf" value="<?= S::escape(S::token()) ?>">
      <?php if ($formMode === 'edit'): ?><input type="hidden" name="maPhanCong"
          value="<?= (int) $old['maPhanCong'] ?>"><?php endif; ?>
      <label>Kỳ tuyển sinh
        <?php if ($formMode === 'edit'): ?>
          <input type="hidden" name="maKyTuyenSinh" value="<?= (int) $old['maKyTuyenSinh'] ?>">
          <input readonly value="<?= S::escape($editYearLabel) ?>" aria-label="Kỳ tuyển sinh">
        <?php else: ?>
          <select name="maKyTuyenSinh" required>
            <?php foreach ($years as $y): ?>
              <option value="<?= (int) $y['maKyTuyenSinh'] ?>"
                <?= (int) $old['maKyTuyenSinh'] === (int) $y['maKyTuyenSinh'] ? 'selected' : '' ?>><?= S::escape($y['namHoc']) ?>
              </option><?php endforeach; ?>
          </select>
        <?php endif; ?>
      </label>
      <label>Giáo viên
        <?php if ($formMode === 'edit'): ?>
          <input readonly
            value="<?= S::escape($old['hoTen'] ?? (array_values(array_filter($teachers, static fn($t) => $t['maNhanSu'] === $old['maNhanSu']))[0]['hoTen'] ?? $old['maNhanSu'])) ?>"
            aria-label="Giáo viên">
          <input type="hidden" name="maNhanSu" value="<?= S::escape($old['maNhanSu']) ?>">
        <?php else: ?>
          <select name="maNhanSu" required>
            <option value="">-- Chọn giáo viên --</option>
            <?php foreach ($teachers as $t): ?>
              <option value="<?= S::escape($t['maNhanSu']) ?>" <?= (string) $old['maNhanSu'] === $t['maNhanSu'] ? 'selected' : '' ?>>
                <?= S::escape($t['hoTen'] . ' (' . $t['maNhanSu'] . ')') ?></option><?php endforeach; ?>
          </select>
        <?php endif; ?>
      </label>
      <label>Môn chấm thi
        <select name="maMonThi" required><?php foreach ($subjects as $s): ?>
            <option value="<?= (int) $s['maMonThi'] ?>" <?= (int) $old['maMonThi'] === (int) $s['maMonThi'] ? 'selected' : '' ?>>
              <?= S::escape($s['tenMonThi']) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label>Phòng thi
        <select name="maPhongThi" required><?php foreach ($rooms as $r): ?>
            <option value="<?= S::escape($r['maPhongThi']) ?>"
              <?= (string) $old['maPhongThi'] === $r['maPhongThi'] ? 'selected' : '' ?>><?= S::escape($r['tenPhongThi']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <div class="form-buttons"><button type="submit" class="btn btn-primary">▣ Lưu phân công</button><a
          class="btn btn-light" href="<?= S::escape($baseUrl) ?>?route=phancong&amp;year=<?= $year ?>">Hủy</a></div>
    </form>
  </section>
<?php endif; ?>

<?php if ($detail !== null): ?>
  <section class="card">
    <div class="card-title">
      <h2>Chi tiết phân công PC<?= str_pad((string) $detail['maPhanCong'], 4, '0', STR_PAD_LEFT) ?></h2><a class="close"
        href="<?= S::escape($baseUrl) ?>?route=phancong&amp;year=<?= $year ?>">×</a>
    </div>
    <div class="detail-grid">
      <?php foreach (['Giáo viên' => $detail['hoTen'], 'Mã nhân sự' => $detail['maNhanSu'], 'Môn chấm' => $detail['tenMonThi'], 'Phòng thi' => $detail['tenPhongThi'], 'Kỳ tuyển sinh' => $detail['namHoc'], 'Trạng thái' => $detail['trangThai']] as $field => $value): ?>
        <div class="detail"><small><?= S::escape($field) ?></small><strong><?= S::escape($value) ?></strong></div>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>

<section class="card">
  <div class="card-title">
    <h2>Danh sách phân công chấm thi</h2><a class="btn btn-primary"
      href="<?= S::escape($baseUrl) ?>?<?= S::escape(http_build_query(['route' => 'phancong', 'year' => $year, 'action' => 'create'])) ?>">＋
      Thêm phân công</a>
  </div>
  <form class="filters" method="get" action="<?= S::escape($baseUrl) ?>">
    <input type="hidden" name="route" value="phancong">
    <label>Tên giáo viên / Mã PC<input name="q" value="<?= S::escape($query) ?>" placeholder="Nhập từ khóa..."
        maxlength="100"></label>
    <label>Môn chấm<select name="subject">
        <option value="">Tất cả môn</option><?php foreach ($subjects as $s): ?>
          <option value="<?= (int) $s['maMonThi'] ?>" <?= $subject === (int) $s['maMonThi'] ? 'selected' : '' ?>>
            <?= S::escape($s['tenMonThi']) ?></option><?php endforeach; ?>
      </select></label>
    <label>Kỳ tuyển sinh<select name="year"><?php foreach ($years as $y): ?>
          <option value="<?= (int) $y['maKyTuyenSinh'] ?>" <?= $year === (int) $y['maKyTuyenSinh'] ? 'selected' : '' ?>>
            <?= S::escape($y['namHoc']) ?></option><?php endforeach; ?>
      </select></label>
    <button type="submit" class="btn btn-primary">Tìm kiếm</button>
  </form>
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>Mã PC</th>
          <th>Giáo viên</th>
          <th>Môn chấm</th>
          <th>Phòng thi</th>
          <th>Trạng thái</th>
          <th>Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="6" class="empty">Không tìm thấy phân công.</td>
          </tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr class="<?= $saved === (int) $r['maPhanCong'] ? 'new-row' : '' ?>">
            <td><strong
                class="pc-id">PC<?= str_pad((string) $r['maPhanCong'], 4, '0', STR_PAD_LEFT) ?></strong><?php if ($saved === (int) $r['maPhanCong']): ?>
                <div class="saved-tag">Mới lưu</div><?php endif; ?>
            </td>
            <td><strong class="teacher-name"><?= S::escape($r['hoTen']) ?></strong><small
                class="teacher-code"><?= S::escape($r['maNhanSu']) ?></small></td>
            <td><strong><?= S::escape($r['tenMonThi']) ?></strong></td>
            <td><strong><?= S::escape($r['tenPhongThi']) ?></strong></td>
            <td><span class="status"><?= S::escape($r['trangThai']) ?></span></td>
            <td class="action-cell"><a class="action action-view"
                href="<?= S::escape($baseUrl) ?>?<?= S::escape(http_build_query(['route' => 'phancong', 'year' => $year, 'action' => 'detail', 'id' => $r['maPhanCong']])) ?>">Xem</a><a
                class="action action-edit"
                href="<?= S::escape($baseUrl) ?>?<?= S::escape(http_build_query(['route' => 'phancong', 'year' => $year, 'action' => 'edit', 'id' => $r['maPhanCong']])) ?>">Sửa</a>
              <form class="inline-form delete-form" method="post" action="<?= S::escape($baseUrl) ?>?route=delete"
                data-confirm="Xóa phân công PC<?= (int) $r['maPhanCong'] ?> của <?= S::escape($r['hoTen']) ?>?">
                <input type="hidden" name="_csrf" value="<?= S::escape(S::token()) ?>"><input type="hidden"
                  name="maPhanCong" value="<?= (int) $r['maPhanCong'] ?>"><button type="submit"
                  class="action action-delete">Xóa</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>