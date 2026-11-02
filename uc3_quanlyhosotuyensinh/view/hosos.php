<?php
require_once __DIR__ . '/../controller/cHoSo.php';

$m = new clsCHoSo();

$ky = max(1, (int)($_POST['ky'] ?? $_GET['ky'] ?? 1));
$q = trim($_GET['q'] ?? '');

$msg = '';
$error = '';

// Lấy dữ liệu xem trước đã lưu trong Session
$preview = (
    ($_SESSION['preview_ky'] ?? null) === $ky
) ? ($_SESSION['preview'] ?? []) : [];


// Xử lý các thao tác từ giao diện
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    try {
        $action = $_POST['action'] ?? '';

        // Tải file Excel và xem trước dữ liệu
        if ($action === 'preview') {

            $preview = $m->xuLyImport(
                $ky,
                $_SESSION['admin']
            );

            $_SESSION['preview'] = $preview;
            $_SESSION['preview_ky'] = $ky;

            $msg = 'Đã kiểm tra file. Vui lòng xem trước rồi xác nhận tạo hồ sơ.';
        }

        // Xác nhận tạo hồ sơ
        elseif ($action === 'save') {

            if (empty($preview)) {
                throw new RuntimeException(
                    'Chưa có danh sách hợp lệ để xác nhận.'
                );
            }

            if (($_SESSION['preview_ky'] ?? 0) !== $ky) {
                throw new RuntimeException(
                    'Kỳ tuyển sinh không khớp với dữ liệu xem trước.'
                );
            }

            $ok = 0;
            $fail = 0;

            foreach ($preview as $entry) {

                // Chỉ xử lý các dòng hợp lệ
                if ($entry['status'] !== 'Hợp lệ') {
                    continue;
                }

                try {
                    $maThiSinh = $entry['row']['maThiSinh'];

                    // Kiểm tra hồ sơ đã tồn tại
                    if (!$m->tonTai($maThiSinh, $ky)) {

                        $m->them(
                            $entry['row'],
                            $ky,
                            $_SESSION['admin']
                        );

                        $ok++;
                    }
                } catch (Throwable $e) {
                    $fail++;
                    error_log('Lỗi lưu hồ sơ UC03: ' . $e->getMessage());
                    $error = 'Lỗi lưu hồ sơ: ' . $e->getMessage();
                }
            }

            // Xóa dữ liệu xem trước sau khi xử lý
            unset(
                $_SESSION['preview'],
                $_SESSION['preview_ky']
            );

            $preview = [];

            $msg = "Đã tạo $ok hồ sơ; $fail dòng lỗi khi lưu.";
        }

        // Hủy hồ sơ và ghi nhận lý do
        elseif ($action === 'cancel') {
            $id = (int)($_POST['id'] ?? 0);
            $lyDo = trim((string)($_POST['lyDoHuy'] ?? ''));
            $lyDoKhac = trim((string)($_POST['lyDoKhac'] ?? ''));
            $danhSachLyDo = [
                'Chuyển địa phương',
                'Không còn nhu cầu dự tuyển',
                'Không đủ điều kiện dự tuyển',
                'Lập hồ sơ nhầm',
                'Khác'
            ];

            if (!in_array($lyDo, $danhSachLyDo, true)) {
                throw new RuntimeException('Vui lòng chọn lý do hủy hợp lệ.');
            }
            if ($lyDo === 'Khác') {
                $lyDo = $lyDoKhac;
            }
            if ($lyDo === '' || mb_strlen($lyDo, 'UTF-8') > 255) {
                throw new RuntimeException('Vui lòng nhập lý do hủy không quá 255 ký tự.');
            }

            if ($m->huy($id, $_SESSION['admin'], $lyDo)) {
                $msg = 'Hủy hồ sơ thành công.';
            } else {
                $error = 'Không thể hủy: hồ sơ đã có nguyện vọng hoặc không ở trạng thái Đã lập.';
            }
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}


// Lấy danh sách hồ sơ và kỳ tuyển sinh
$list = $m->dsHoSo($ky, $q);
$kys = $m->dsKy();
?>

<!-- Tiêu đề trang -->
<div class="page-title">

    <div>
        <h1>Quản lý hồ sơ tuyển sinh</h1>

        <p class="muted">
            Quản lý danh sách học sinh và hồ sơ theo kỳ tuyển sinh
        </p>
    </div>

</div>


<!-- Thông báo thành công -->
<?php if ($msg): ?>
    <div class="alert success">
        <?= h($msg) ?>
    </div>
<?php endif; ?>


<!-- Thông báo lỗi -->
<?php if ($error): ?>
    <div class="alert error">
        <?= h($error) ?>
    </div>
<?php endif; ?>


<!-- Danh sách hồ sơ tuyển sinh -->
<section class="panel">

    <h2>Danh sách hồ sơ</h2>

    <!-- Bộ lọc tìm kiếm -->
    <form class="filter" method="get">

        <input
            type="hidden"
            name="page"
            value="hosos">

        <label>
            Kỳ tuyển sinh

            <select name="ky">

                <?php foreach ($kys as $k): ?>

                    <option
                        value="<?= (int)$k['maKy'] ?>"
                        <?= $ky === (int)$k['maKy'] ? 'selected' : '' ?>>
                        <?= h($k['tenKy']) ?>
                    </option>

                <?php endforeach; ?>

            </select>
        </label>

        <label>
            Tìm mã hồ sơ / mã học sinh / họ tên

            <input
                type="text"
                name="q"
                value="<?= h($q) ?>"
                placeholder="Nhập từ khóa">
        </label>

        <button type="submit" class="btn primary">
            Tra cứu
        </button>

    </form>


    <!-- Bảng danh sách hồ sơ -->
    <div class="scroll">

        <table>

            <thead>
                <tr>
                    <th>Mã hồ sơ</th>
                    <th>Mã học sinh</th>
                    <th>Họ và tên</th>
                    <th>Trường THCS</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($list as $x): ?>

                    <tr>

                        <td><?= h($x['maHoSo']) ?></td>

                        <td><?= h($x['maThiSinh']) ?></td>

                        <td><?= h($x['hoTen']) ?></td>

                        <td><?= h($x['tenTruong']) ?></td>

                        <td>
                            <?php
                            $trangThaiClass = 'status-badge';

                            if ($x['trangThai'] === 'Đã lập') {
                                $trangThaiClass .= ' status-valid';
                            } elseif ($x['trangThai'] === 'Đã hủy') {
                                $trangThaiClass .= ' status-invalid';
                            } else {
                                $trangThaiClass .= ' status-exists';
                            }
                            ?>
                            <span class="<?= $trangThaiClass ?>">
                                <?= h($x['trangThai']) ?>
                            </span>
                        </td>

                        <!-- Xem / Sửa / Hủy -->
                        <td class="actions-cell">

                            <div class="record-actions">

                                <!-- Xem chi tiết hồ sơ -->
                                <a
                                    class="action-btn action-view"
                                    href="index.php?page=edit&mode=view&id=<?= (int)$x['id'] ?>"
                                    aria-label="Xem hồ sơ">
                                    Xem
                                </a>

                                <?php if ($x['trangThai'] === 'Đã lập'): ?>

                                    <!-- Sửa hồ sơ -->
                                    <a
                                        class="action-btn action-edit"
                                        href="index.php?page=edit&mode=edit&id=<?= (int)$x['id'] ?>"
                                        aria-label="Sửa hồ sơ">
                                        Sửa
                                    </a>

                                    <!-- Mở hộp thoại nhập lý do hủy -->
                                    <button
                                        type="button"
                                        class="action-btn action-delete btn-open-cancel"
                                        data-id="<?= (int)$x['id'] ?>"
                                        data-ma="<?= h($x['maHoSo']) ?>"
                                        data-ten="<?= h($x['hoTen']) ?>">
                                        Hủy
                                    </button>

                                <?php endif; ?>

                            </div>

                        </td>

                    </tr>

                <?php endforeach; ?>

                <!-- Không có hồ sơ -->
                <?php if (empty($list)): ?>

                    <tr>
                        <td colspan="6" class="empty">
                            Chưa có hồ sơ phù hợp.
                            Hãy tải dữ liệu từ Excel để bắt đầu.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>


<!-- Nhập danh sách học sinh từ Excel -->
<section class="panel">

    <h2>Nhập danh sách học sinh từ Excel</h2>

    <form
        method="post"
        enctype="multipart/form-data"
        class="upload">

        <input
            type="hidden"
            name="csrf"
            value="<?= h(csrf()) ?>">

        <input
            type="hidden"
            name="ky"
            value="<?= $ky ?>">

        <input
            type="hidden"
            name="action"
            value="preview">

        <input
            type="file"
            name="file"
            accept=".xlsx,.csv"
            required>

        <button type="submit" class="btn primary">
            Tải lên và kiểm tra
        </button>

    </form>


    <!-- Bảng xem trước dữ liệu -->
    <?php if (!empty($preview)): ?>

        <h3>Bảng xem trước dữ liệu</h3>

        <div class="scroll">
            <table class="preview-table">
                <thead>
                    <tr>
                        <th>Dòng</th>
                        <th>Mã học sinh</th>
                        <th>Họ và tên</th>
                        <th>Trường THCS</th>
                        <th>Trạng thái</th>
                        <th>Lý do</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($preview as $x): ?>
                        <tr>
                            <td><?= (int)$x['dong'] ?></td>
                            <td><?= h($x['row']['maThiSinh']) ?></td>
                            <td><?= h($x['row']['hoTen']) ?></td>
                            <td><?= h($x['row']['maTruong']) ?></td>

                            <td>
                                <?php
                                $statusClass = 'status-exists';

                                if ($x['status'] === 'Hợp lệ') {
                                    $statusClass = 'status-valid';
                                } elseif ($x['status'] === 'Không hợp lệ') {
                                    $statusClass = 'status-invalid';
                                }
                                ?>

                                <span class="status-badge <?= $statusClass ?>">
                                    <?= h($x['status']) ?>
                                </span>
                            </td>

                            <td><?= h($x['note']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Xác nhận tạo hồ sơ -->
        <form method="post" class="preview-confirm">

            <input
                type="hidden"
                name="csrf"
                value="<?= h(csrf()) ?>">

            <input
                type="hidden"
                name="ky"
                value="<?= $ky ?>">

            <input
                type="hidden"
                name="action"
                value="save">

            <button
                type="submit"
                class="btn primary"
                onclick="return confirm('Tạo hồ sơ cho tất cả dòng hợp lệ?')">
                Xác nhận tạo hồ sơ hợp lệ
            </button>

        </form>

    <?php endif; ?>

</section>
<!-- Hộp thoại hủy hồ sơ -->
<div id="cancelModal" class="cancel-modal" hidden role="dialog" aria-modal="true" aria-labelledby="cancelTitle">
    <div class="cancel-modal-box">
        <div class="cancel-modal-header">
            <h2 id="cancelTitle">Xác nhận hủy hồ sơ</h2>
            <button type="button" class="cancel-close" id="closeCancelModal" aria-label="Đóng">&times;</button>
        </div>
        <p class="cancel-description">Vui lòng kiểm tra thông tin và chọn lý do hủy hồ sơ.</p>
        <div class="cancel-info">
            <p><strong>Mã hồ sơ:</strong> <span id="cancelMaHoSo"></span></p>
            <p><strong>Họ và tên:</strong> <span id="cancelHoTen"></span></p>
        </div>
        <form id="cancelForm" method="post">
            <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
            <input type="hidden" name="ky" value="<?= (int)$ky ?>">
            <input type="hidden" name="action" value="cancel">
            <input type="hidden" name="id" id="cancelId">
            <label for="lyDoHuy">Lý do hủy hồ sơ <span class="required">*</span></label>
            <select name="lyDoHuy" id="lyDoHuy" required>
                <option value="">-- Chọn lý do hủy --</option>
                <option value="Chuyển địa phương">Chuyển địa phương</option>
                <option value="Không còn nhu cầu dự tuyển">Không còn nhu cầu dự tuyển</option>
                <option value="Không đủ điều kiện dự tuyển">Không đủ điều kiện dự tuyển</option>
                <option value="Lập hồ sơ nhầm">Lập hồ sơ nhầm</option>
                <option value="Khác">Lý do khác</option>
            </select>
            <div id="otherReasonBox" hidden>
                <label for="lyDoKhac">Nhập lý do cụ thể <span class="required">*</span></label>
                <textarea id="lyDoKhac" name="lyDoKhac" rows="3" maxlength="255" placeholder="Nhập lý do hủy hồ sơ..."></textarea>
            </div>
            <div class="cancel-buttons">
                <button type="button" class="btn" id="cancelBack">Quay lại</button>
                <button type="submit" class="btn btn-confirm-delete">Xác nhận hủy</button>
            </div>
        </form>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('cancelModal');
        const form = document.getElementById('cancelForm');
        const reason = document.getElementById('lyDoHuy');
        const other = document.getElementById('lyDoKhac');
        const otherBox = document.getElementById('otherReasonBox');
        let lastButton = null;

        document.querySelectorAll('.btn-open-cancel').forEach(function(button) {
            button.addEventListener('click', function() {
                lastButton = button;
                form.reset();
                document.getElementById('cancelId').value = button.dataset.id;
                document.getElementById('cancelMaHoSo').textContent = button.dataset.ma;
                document.getElementById('cancelHoTen').textContent = button.dataset.ten;
                otherBox.hidden = true;
                other.required = false;
                modal.hidden = false;
                reason.focus();
            });
        });

        reason.addEventListener('change', function() {
            const showOther = reason.value === 'Khác';
            otherBox.hidden = !showOther;
            other.required = showOther;
            if (!showOther) other.value = '';
        });

        function closeModal() {
            modal.hidden = true;
            if (lastButton) lastButton.focus();
        }
        document.getElementById('closeCancelModal').addEventListener('click', closeModal);
        document.getElementById('cancelBack').addEventListener('click', closeModal);
        modal.addEventListener('click', function(event) {
            if (event.target === modal) closeModal();
        });
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && !modal.hidden) closeModal();
        });
        form.addEventListener('submit', function(event) {
            if (reason.value === 'Khác' && !other.value.trim()) {
                event.preventDefault();
                other.focus();
                return;
            }
            if (!confirm('Bạn có chắc chắn muốn hủy hồ sơ này?')) {
                event.preventDefault();
            }
        });
    });
</script>