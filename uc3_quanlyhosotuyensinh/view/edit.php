<?php
require_once __DIR__ . '/../controller/cHoSo.php';

$m = new clsCHoSo();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$mode = ($_GET['mode'] ?? 'view') === 'edit' ? 'edit' : 'view';

$readOnly = $mode !== 'edit';

$record = $m->motHoSo($id);

$msg = '';
$err = '';

// Kiểm tra hồ sơ có tồn tại hay không
if (!$record) {
    echo '<div class="alert error">Không tìm thấy hồ sơ.</div>';
    return;
}

// Xử lý cập nhật hồ sơ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    if ($readOnly) {
        http_response_code(403);
        $err = 'Chế độ xem không cho phép cập nhật hồ sơ.';
    } else {

        // Kiểm tra trạng thái hồ sơ
        if ($record['trangThai'] !== 'Đã lập') {
            $err = 'Hồ sơ đã hủy không được chỉnh sửa.';
        } else {

            // Lấy dữ liệu từ biểu mẫu
            $data = [
                'hoTen' => trim($_POST['hoTen'] ?? ''),
                'ngaySinh' => trim($_POST['ngaySinh'] ?? ''),
                'gioiTinh' => $_POST['gioiTinh'] ?? '',
                'soDienThoai' => trim($_POST['soDienThoai'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'diaChi' => trim($_POST['diaChi'] ?? '')
            ];

            $parts = [];

            // Kiểm tra dữ liệu nhập
            $validDate = preg_match(
                '/^(\d{4})-(\d{2})-(\d{2})$/',
                $data['ngaySinh'],
                $parts
            ) && checkdate(
                (int)($parts[2] ?? 0),
                (int)($parts[3] ?? 0),
                (int)($parts[1] ?? 0)
            );

            $validGender = in_array(
                $data['gioiTinh'],
                ['Nam', 'Nữ', 'Khác'],
                true
            );

            $validEmail = (
                $data['email'] === '' ||
                filter_var($data['email'], FILTER_VALIDATE_EMAIL)
            );

            $validPhone = (
                $data['soDienThoai'] === '' ||
                preg_match(
                    '/^0[0-9]{9}$/D',
                    $data['soDienThoai']
                )
            );

            if (
                $data['hoTen'] === '' ||
                !$validDate ||
                !$validGender ||
                !$validEmail ||
                !$validPhone
            ) {
                $err = 'Vui lòng kiểm tra họ tên, ngày sinh, giới tính, số điện thoại và email.';
            } else {

                try {
                    // Cập nhật hồ sơ thông qua Controller
                    $result = $m->sua(
                        $id,
                        $data,
                        $_SESSION['admin']
                    );

                    if ($result) {
                        $msg = 'Cập nhật hồ sơ thành công.';
                    } else {
                        $err = 'Hồ sơ không tồn tại hoặc không được phép cập nhật.';
                    }

                    // Lấy lại dữ liệu sau khi cập nhật
                    $record = $m->motHoSo($id);
                } catch (Throwable $e) {
                    $err = 'Không thể cập nhật hồ sơ.';
                }
            }
        }
    }
}
?>

<!-- Tiêu đề trang -->
<div class="page-title">
    <h1>
        <?= $readOnly
            ? 'Chi tiết hồ sơ tuyển sinh'
            : 'Cập nhật hồ sơ tuyển sinh'
        ?>
    </h1>

    <a class="btn" href="index.php?page=hosos">
        ← Quay lại
    </a>
</div>

<!-- Thông báo thành công -->
<?php if ($msg): ?>
    <div class="alert success">
        <?= h($msg) ?>
    </div>
<?php endif; ?>

<!-- Thông báo lỗi -->
<?php if ($err): ?>
    <div class="alert error">
        <?= h($err) ?>
    </div>
<?php endif; ?>

<section class="panel">

    <!-- Nút chuyển sang chế độ sửa -->
    <?php if (
        $readOnly &&
        $record['trangThai'] === 'Đã lập'
    ): ?>
        <div class="record-actions" style="margin-bottom:14px">
            <a
                class="action-btn action-edit"
                href="index.php?page=edit&mode=edit&id=<?= $id ?>">
                Sửa hồ sơ
            </a>
        </div>
    <?php endif; ?>

    <!-- Thông tin hồ sơ -->
    <p>
        <strong>Mã hồ sơ:</strong>
        <?= h($record['maHoSo']) ?>

        · <strong>Mã học sinh:</strong>
        <?= h($record['maThiSinh']) ?>

        · <strong>Trạng thái:</strong>
        <?= h($record['trangThai']) ?>
    </p>

    <!-- Thông tin hủy được lưu để tra cứu -->
    <?php if ($record['trangThai'] === 'Đã hủy'): ?>
        <div class="alert error">
            <p><strong>Lý do hủy:</strong> <?= h($record['lyDoHuy'] ?: 'Chưa ghi nhận') ?></p>
            <p><strong>Người hủy:</strong> <?= h($record['nguoiHuy'] ?? '') ?></p>
            <p><strong>Thời gian hủy:</strong> <?= h($record['ngayHuy'] ?? '') ?></p>
        </div>
    <?php endif; ?>

    <!-- Biểu mẫu hồ sơ -->
    <form method="post" class="formgrid">

        <input
            type="hidden"
            name="csrf"
            value="<?= h(csrf()) ?>">

        <input
            type="hidden"
            name="id"
            value="<?= $id ?>">

        <fieldset
            class="detail-fieldset"
            <?= $readOnly ? 'disabled' : '' ?>>

            <!-- Họ tên -->
            <label>
                Họ và tên

                <input
                    type="text"
                    name="hoTen"
                    value="<?= h($record['hoTen']) ?>"
                    required>
            </label>

            <!-- Ngày sinh -->
            <label>
                Ngày sinh

                <input
                    type="date"
                    name="ngaySinh"
                    value="<?= h($record['ngaySinh']) ?>"
                    required>
            </label>

            <!-- Giới tính -->
            <label>
                Giới tính

                <select name="gioiTinh">
                    <?php foreach (['Nam', 'Nữ', 'Khác'] as $g): ?>
                        <option
                            value="<?= h($g) ?>"
                            <?= $record['gioiTinh'] === $g
                                ? 'selected'
                                : ''
                            ?>>
                            <?= h($g) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <!-- CCCD chỉ được xem -->
            <label>
                CCCD (chỉ xem)

                <input
                    type="text"
                    value="<?= h($record['cccd']) ?>"
                    readonly>
            </label>

            <!-- Số điện thoại -->
            <label>
                Số điện thoại

                <input
                    type="text"
                    name="soDienThoai"
                    value="<?= h($record['soDienThoai']) ?>">
            </label>

            <!-- Email -->
            <label>
                Email

                <input
                    type="email"
                    name="email"
                    value="<?= h($record['email']) ?>">
            </label>

            <!-- Địa chỉ -->
            <label class="full">
                Địa chỉ

                <input
                    type="text"
                    name="diaChi"
                    value="<?= h($record['diaChi']) ?>">
            </label>

            <!-- Nút lưu thay đổi -->
            <?php if (!$readOnly): ?>
                <button
                    type="submit"
                    class="btn primary"
                    <?= $record['trangThai'] !== 'Đã lập'
                        ? 'disabled'
                        : ''
                    ?>>
                    Lưu thay đổi
                </button>
            <?php endif; ?>

        </fieldset>
    </form>
</section>