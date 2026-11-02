
<?php
require_once __DIR__ . '/ketnoi.php';

// Model quản lý hồ sơ tuyển sinh
class clsMHoSo
{
    private mysqli $db;

    // Khởi tạo kết nối cơ sở dữ liệu
    public function __construct()
    {
        $this->db = db();
    }

    // Lấy danh sách kỳ tuyển sinh
    public function dsKy(): array
    {
        $sql = "SELECT *
                FROM kytuyensinh
                ORDER BY nam DESC";

        $result = $this->db->query($sql);

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Lấy danh sách trường THCS
    public function dsTruong(): array
    {
        $sql = "SELECT *
                FROM truongthcs
                ORDER BY tenTruong";

        $result = $this->db->query($sql);

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Lấy danh sách hồ sơ theo kỳ tuyển sinh và từ khóa
    public function dsHoSo(int $ky, string $q = ''): array
    {
        $sql = "SELECT hs.*,
                       ts.hoTen,
                       ts.ngaySinh,
                       ts.gioiTinh,
                       ts.cccd,
                       t.tenTruong,
                       k.nam
                FROM hosotuyensinh hs
                JOIN thisinh ts
                    ON ts.maThiSinh = hs.maThiSinh
                JOIN truongthcs t
                    ON t.maTruong = hs.maTruong
                JOIN kytuyensinh k
                    ON k.maKy = hs.maKy
                WHERE hs.maKy = ?
                  AND (
                      hs.maHoSo LIKE ?
                      OR ts.hoTen LIKE ?
                      OR ts.maThiSinh LIKE ?
                  )
                ORDER BY hs.id DESC";

        $stmt = $this->db->prepare($sql);

        $like = '%' . $q . '%';

        $stmt->bind_param(
            'isss',
            $ky,
            $like,
            $like,
            $like
        );

        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Lấy thông tin chi tiết một hồ sơ
    public function motHoSo(int $id): ?array
    {
        $sql = "SELECT hs.*,
                       ts.hoTen,
                       ts.ngaySinh,
                       ts.gioiTinh,
                       ts.cccd,
                       ts.soDienThoai,
                       ts.email,
                       ts.diaChi
                FROM hosotuyensinh hs
                JOIN thisinh ts
                    ON ts.maThiSinh = hs.maThiSinh
                WHERE hs.id = ?";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param('i', $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // Kiểm tra học sinh đã có hồ sơ trong kỳ tuyển sinh
    public function tonTai(string $ma, int $ky): bool
    {
        $sql = "SELECT 1
                FROM hosotuyensinh
                WHERE maThiSinh = ?
                  AND maKy = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param('si', $ma, $ky);
        $stmt->execute();

        return (bool)$stmt->get_result()->fetch_row();
    }

    // Kiểm tra trường THCS có tồn tại hay không
    public function truongTonTai(string $ma): bool
    {
        $sql = "SELECT 1
                FROM truongthcs
                WHERE maTruong = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param('s', $ma);
        $stmt->execute();

        return (bool)$stmt->get_result()->fetch_row();
    }


    // Tạo mới hồ sơ tuyển sinh
    public function them(array $r, int $ky, string $user): void
    {
        $this->db->begin_transaction();

        try {
            // Chuẩn bị dữ liệu thí sinh
            $maThiSinh = $r['maThiSinh'];
            $hoTen = $r['hoTen'];
            $ngaySinh = $r['ngaySinh'];
            $gioiTinh = $r['gioiTinh'];
            $cccd = $r['cccd'] ?? '';
            $soDienThoai = $r['soDienThoai'] ?? '';
            $email = $r['email'] ?? '';
            $diaChi = $r['diaChi'] ?? '';
            $maTruong = $r['maTruong'];

            // Lưu thông tin thí sinh
            $sql = "INSERT INTO thisinh
                        (maThiSinh, hoTen, ngaySinh, gioiTinh, cccd,
                         soDienThoai, email, diaChi)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                   ON DUPLICATE KEY UPDATE
                    soDienThoai = IF(
                        VALUES(soDienThoai) <> '',
                        VALUES(soDienThoai),
                        soDienThoai
                    ),
                    email = IF(
                        VALUES(email) <> '',
                        VALUES(email),
                        email
                    ),
                    diaChi = IF(
                        VALUES(diaChi) <> '',
                        VALUES(diaChi),
                        diaChi
                    )";

            $stmt = $this->db->prepare($sql);

            $stmt->bind_param(
                'ssssssss',
                $maThiSinh,
                $hoTen,
                $ngaySinh,
                $gioiTinh,
                $cccd,
                $soDienThoai,
                $email,
                $diaChi
            );

            $stmt->execute();
            $stmt->close();

            // Tạo mã hồ sơ tuyển sinh
            $maHoSo = 'HS' . date('ymd')
                . strtoupper(substr(bin2hex(random_bytes(4)), 0, 7));

            // Lưu hồ sơ tuyển sinh
            $sql = "INSERT INTO hosotuyensinh
                        (maHoSo, maThiSinh, maTruong,
                         maKy, trangThai, nguoiTao)
                    VALUES (?, ?, ?, ?, 'Đã lập', ?)";

            $stmt = $this->db->prepare($sql);

            $stmt->bind_param(
                'sssis',
                $maHoSo,
                $maThiSinh,
                $maTruong,
                $ky,
                $user
            );

            $stmt->execute();
            $stmt->close();

            // Hoàn tất giao dịch
            $this->db->commit();
        } catch (Throwable $e) {

            // Có lỗi thì hoàn tác dữ liệu
            $this->db->rollback();

            throw $e;
        }
    }
    // Cập nhật hồ sơ tuyển sinh
    public function sua(int $id, array $r, string $user): bool
    {
        $this->db->begin_transaction();

        try {
            // Kiểm tra hồ sơ hiện tại
            $current = $this->motHoSo($id);

            if (
                !$current ||
                $current['trangThai'] !== 'Đã lập'
            ) {
                $this->db->rollback();
                return false;
            }

            // Cập nhật thông tin thí sinh
            $sql = "UPDATE thisinh
                    SET hoTen = ?,
                        ngaySinh = ?,
                        gioiTinh = ?,
                        soDienThoai = ?,
                        email = ?,
                        diaChi = ?
                    WHERE maThiSinh = ?";

            $stmt = $this->db->prepare($sql);

            $stmt->bind_param(
                'sssssss',
                $r['hoTen'],
                $r['ngaySinh'],
                $r['gioiTinh'],
                $r['soDienThoai'],
                $r['email'],
                $r['diaChi'],
                $current['maThiSinh']
            );

            $stmt->execute();

            // Ghi nhận người và thời gian cập nhật
            $sql = "UPDATE hosotuyensinh
                    SET nguoiCapNhat = ?,
                        ngayCapNhat = NOW()
                    WHERE id = ?
                      AND trangThai = 'Đã lập'";

            $stmt = $this->db->prepare($sql);

            $stmt->bind_param('si', $user, $id);
            $stmt->execute();

            if ($stmt->affected_rows !== 1) {
                throw new RuntimeException(
                    'Không thể cập nhật hồ sơ.'
                );
            }

            $this->db->commit();

            return true;
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    // Hủy hồ sơ tuyển sinh
    public function huy(int $id, string $user, string $lyDoHuy): bool
    {
        $lyDoHuy = trim($lyDoHuy);
        if ($lyDoHuy === '' || mb_strlen($lyDoHuy, 'UTF-8') > 255) {
            return false;
        }

        $sql = "UPDATE hosotuyensinh
                SET trangThai = 'Đã hủy',
                    lyDoHuy = ?,
                    nguoiHuy = ?,
                    ngayHuy = NOW()
                WHERE id = ?
                  AND trangThai = 'Đã lập'
                  AND NOT EXISTS (
                      SELECT 1
                      FROM nguyenvong
                      WHERE nguyenvong.maHoSo =
                            hosotuyensinh.maHoSo
                  )";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param('ssi', $lyDoHuy, $user, $id);
        $stmt->execute();

        return $stmt->affected_rows === 1;
    }
}

// Tương thích với tên lớp cũ trong dự án
class_alias(clsMHoSo::class, 'mHoSo');
