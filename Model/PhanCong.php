<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class PhanCong
{
    public function db(): PDO
    {
        return Database::connection();
    }

    public function years(): array
    {
        return $this->db()->query('SELECT maKyTuyenSinh,namHoc FROM KyTuyenSinh ORDER BY namHoc DESC')->fetchAll();
    }

    public function subjects(): array
    {
        return $this->db()->query('SELECT maMonThi,tenMonThi FROM MonThi ORDER BY maMonThi')->fetchAll();
    }

    public function teachers(): array
    {
        return $this->db()->query("SELECT maNhanSu,hoTen FROM NhanSu WHERE trangThaiHoatDong='Đang hoạt động' ORDER BY hoTen")->fetchAll();
    }

    public function rooms(): array
    {
        return $this->db()->query("SELECT maPhongThi,tenPhongThi FROM PhongThi WHERE trangThai='Hoạt động' ORDER BY maPhongThi")->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db()->prepare("SELECT pc.*, h.maKyTuyenSinh,k.namHoc,n.hoTen,m.tenMonThi,p.tenPhongThi
           FROM PhanCong pc
           JOIN HoiDong h ON h.maHoiDong=pc.maHoiDong
           JOIN KyTuyenSinh k ON k.maKyTuyenSinh=h.maKyTuyenSinh
           JOIN NhanSu n ON n.maNhanSu=pc.maNhanSu
           JOIN MonThi m ON m.maMonThi=pc.maMonThi
           JOIN PhongThi p ON p.maPhongThi=pc.maPhongThi
           WHERE pc.maPhanCong=? AND h.loaiHoiDong='CHAM_THI'");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function list(int $year, ?int $subject = null, string $q = ''): array
    {
        $sql = "SELECT pc.maPhanCong,pc.maNhanSu,pc.maMonThi,pc.maPhongThi,pc.ngayPhanCong,pc.trangThai,
                       n.hoTen,m.tenMonThi,p.tenPhongThi,h.maKyTuyenSinh,k.namHoc
                FROM PhanCong pc
                JOIN HoiDong h ON pc.maHoiDong=h.maHoiDong
                JOIN KyTuyenSinh k ON h.maKyTuyenSinh=k.maKyTuyenSinh
                JOIN NhanSu n ON pc.maNhanSu=n.maNhanSu
                JOIN MonThi m ON pc.maMonThi=m.maMonThi
                JOIN PhongThi p ON pc.maPhongThi=p.maPhongThi
                WHERE h.loaiHoiDong='CHAM_THI' AND h.maKyTuyenSinh=:year";
        $params = ['year' => $year];
        if ($subject !== null) {
            $sql .= ' AND pc.maMonThi=:subject';
            $params['subject'] = $subject;
        }
        if ($q !== '') {
            $sql .= ' AND (n.hoTen LIKE :q1 OR n.maNhanSu LIKE :q2 OR CAST(pc.maPhanCong AS CHAR) LIKE :q3)';
            $params['q1'] = $params['q2'] = $params['q3'] = '%' . $q . '%';
        }
        $sql .= ' ORDER BY pc.maPhanCong DESC';
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function councilId(int $year): ?int
    {
        $stmt = $this->db()->prepare("SELECT maHoiDong FROM HoiDong WHERE maKyTuyenSinh=? AND loaiHoiDong='CHAM_THI' AND trangThai='Đang hoạt động'");
        $stmt->execute([$year]);
        $result = $stmt->fetchColumn();
        return $result === false ? null : (int) $result;
    }

    /**
     * Validate real DB references and business rules before every write, including import.
     * Expected fields: maKyTuyenSinh, maNhanSu, maMonThi, maPhongThi.
     */
    public function validationError(array $input, ?int $ignoreId = null): ?string
    {
        $year = (int) ($input['maKyTuyenSinh'] ?? 0);
        $teacher = trim((string) ($input['maNhanSu'] ?? ''));
        $subject = (int) ($input['maMonThi'] ?? 0);
        $room = trim((string) ($input['maPhongThi'] ?? ''));
        if ($year <= 0 || !$this->councilId($year))
            return 'Kỳ tuyển sinh chưa có hội đồng chấm thi hoạt động.';
        if ($teacher === '' || $subject <= 0 || $room === '')
            return 'Vui lòng nhập đầy đủ giáo viên, môn chấm và phòng thi.';
        $stmt = $this->db()->prepare("SELECT n.hoTen,n.trangThaiHoatDong,
            EXISTS(SELECT 1 FROM ChuyenMonMonThi cm WHERE cm.maChuyenMon=n.maChuyenMon AND cm.maMonThi=?) AS phuHop
            FROM NhanSu n WHERE n.maNhanSu=?");
        $stmt->execute([$subject, $teacher]);
        $staff = $stmt->fetch();
        if (!$staff || $staff['trangThaiHoatDong'] !== 'Đang hoạt động')
            return 'Giáo viên không tồn tại hoặc đã ngừng hoạt động.';
        if (!(int) $staff['phuHop'])
            return $staff['hoTen'] . ' không có chuyên môn phù hợp với môn đã chọn.';
        $stmt = $this->db()->prepare('SELECT COUNT(*) FROM MonThi WHERE maMonThi=?');
        $stmt->execute([$subject]);
        if (!(int) $stmt->fetchColumn())
            return 'Môn thi không tồn tại.';
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM PhongThi WHERE maPhongThi=? AND trangThai='Hoạt động'");
        $stmt->execute([$room]);
        if (!(int) $stmt->fetchColumn())
            return 'Phòng thi không tồn tại hoặc không hoạt động.';
        $params = [$year, $teacher, $subject, $room];
        $sql = "SELECT COUNT(*) FROM PhanCong pc JOIN HoiDong h ON pc.maHoiDong=h.maHoiDong WHERE h.maKyTuyenSinh=? AND h.loaiHoiDong='CHAM_THI' AND pc.maNhanSu=? AND pc.maMonThi=? AND pc.maPhongThi=?";
        if ($ignoreId !== null) {
            $sql .= ' AND pc.maPhanCong<>?';
            $params[] = $ignoreId;
        }
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);
        if ((int) $stmt->fetchColumn() > 0)
            return $staff['hoTen'] . ' đã được phân công môn này tại phòng ' . $room . ' trong kỳ tuyển sinh.';
        return null;
    }

    public function create(array $input, string $actor): int
    {
        $council = $this->councilId((int) $input['maKyTuyenSinh']);
        if (!$council)
            throw new \RuntimeException('Hội đồng chấm thi không tồn tại.');
        $stmt = $this->db()->prepare("INSERT INTO PhanCong(maHoiDong,maNhanSu,maMonThi,maPhongThi,ngayPhanCong,trangThai) VALUES(?,?,?,?,CURDATE(),'Đã phân công')");
        $stmt->execute([$council, $input['maNhanSu'], (int) $input['maMonThi'], $input['maPhongThi']]);
        $id = (int) $this->db()->lastInsertId();
        $this->audit($actor, 'THEM', $id, 'Thêm phân công chấm thi #' . $id);
        return $id;
    }

    public function update(int $id, array $input, string $actor): void
    {
        $council = $this->councilId((int) $input['maKyTuyenSinh']);
        if (!$council)
            throw new \RuntimeException('Hội đồng chấm thi không tồn tại.');
        $stmt = $this->db()->prepare('UPDATE PhanCong SET maHoiDong=?,maNhanSu=?,maMonThi=?,maPhongThi=? WHERE maPhanCong=?');
        $stmt->execute([$council, $input['maNhanSu'], (int) $input['maMonThi'], $input['maPhongThi'], $id]);
        $this->audit($actor, 'CAP_NHAT', $id, 'Cập nhật phân công #' . $id);
    }

    public function delete(int $id, string $actor): void
    {
        $stmt = $this->db()->prepare('DELETE FROM PhanCong WHERE maPhanCong=?');
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0)
            throw new \RuntimeException('Phân công không còn tồn tại.');
        $this->audit($actor, 'XOA', $id, 'Xóa phân công #' . $id);
    }

    private function audit(string $actor, string $action, int $id, string $message): void
    {
        $stmt = $this->db()->prepare('INSERT INTO NhatKyThaoTac(maTaiKhoan,hanhDong,maPhanCong,noiDung) VALUES(?,?,?,?)');
        $stmt->execute([$actor, $action, $id, $message]);
    }
}
