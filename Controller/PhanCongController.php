<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Security;
use App\Core\View;
use App\Models\PhanCong;
use App\Services\ExcelReader;
use PDOException;

final class PhanCongController
{
    private PhanCong $model;
    public function __construct()
    {
        $this->model = new PhanCong();
    }

    private function chosenYear(): int
    {
        $years = $this->model->years();
        $selected = Security::intId($_GET['year'] ?? null);
        $allowed = array_map(static fn($x) => (int) $x['maKyTuyenSinh'], $years);
        return in_array($selected, $allowed, true) ? $selected : ($allowed[0] ?? 0);
    }

    private function common(): array
    {
        return [
            'years' => $this->model->years(),
            'teachers' => $this->model->teachers(),
            'subjects' => $this->model->subjects(),
            'rooms' => $this->model->rooms()
        ];
    }

    public function index(): void
    {
        $common = $this->common();
        $year = $this->chosenYear();
        $subject = Security::intId($_GET['subject'] ?? null);
        $query = trim((string) ($_GET['q'] ?? ''));
        $query = substr($query, 0, 100);
        $action = (string) ($_GET['action'] ?? '');
        $id = Security::intId($_GET['id'] ?? null);
        $editing = ($action === 'edit' && $id) ? $this->model->find($id) : null;
        $detail = ($action === 'detail' && $id) ? $this->model->find($id) : null;
        $formMode = $action === 'create' ? 'create' : ($editing ? 'edit' : null);
        $old = $formMode === 'edit' ? $editing : ['maKyTuyenSinh' => $year, 'maNhanSu' => '', 'maMonThi' => 1, 'maPhongThi' => 'P101'];
        $this->listPage($common, $year, $subject, $query, $formMode, $old, $detail, null);
    }

    private function listPage(array $common, int $year, ?int $subject, string $query, ?string $formMode, ?array $old, ?array $detail, ?string $formError): void
    {
        $rows = $this->model->list($year, $subject, $query);
        $total = $this->model->list($year);
        View::render('phancong/index', array_merge($common, [
            'pageTitle' => 'Quản lý hội đồng chấm thi',
            'year' => $year,
            'subject' => $subject,
            'query' => $query,
            'rows' => $rows,
            'totalRows' => $total,
            'formMode' => $formMode,
            'old' => $old,
            'detail' => $detail,
            'formError' => $formError
        ]));
    }

    private function postedFields(): array
    {
        return [
            'maKyTuyenSinh' => Security::intId($_POST['maKyTuyenSinh'] ?? null) ?? 0,
            'maNhanSu' => substr(trim((string) ($_POST['maNhanSu'] ?? '')), 0, 20),
            'maMonThi' => Security::intId($_POST['maMonThi'] ?? null) ?? 0,
            'maPhongThi' => substr(trim((string) ($_POST['maPhongThi'] ?? '')), 0, 20)
        ];
    }

    public function save(): void
    {
        $id = Security::intId($_POST['maPhanCong'] ?? null);
        $input = $this->postedFields();
        $existing = $id !== null ? $this->model->find($id) : null;
        if ($id !== null && !$existing) {
            View::flash('Phân công không tồn tại.', 'error');
            View::redirect('phancong');
        }
        // Manual edit follows UC21: can change subject/room, not the assigned teacher/year.
        if ($existing) {
            $input['maKyTuyenSinh'] = (int) $existing['maKyTuyenSinh'];
            $input['maNhanSu'] = $existing['maNhanSu'];
        }
        $error = $this->model->validationError($input, $id);
        if ($error !== null) {
            $common = $this->common();
            $this->listPage($common, $input['maKyTuyenSinh'], null, '', $id ? 'edit' : 'create', array_merge($input, ['maPhanCong' => $id]), null, $error);
            return;
        }
        $db = $this->model->db();
        try {
            $db->beginTransaction();
            if ($id === null)
                $id = $this->model->create($input, (string) $_SESSION['user']['id']);
            else
                $this->model->update($id, $input, (string) $_SESSION['user']['id']);
            $db->commit();
            View::flash('Lưu phân công PC' . str_pad((string) $id, 4, '0', STR_PAD_LEFT) . ' thành công.');
            // No old search/filter: newly created row is immediately visible at top.
            View::redirect('phancong', ['year' => $input['maKyTuyenSinh'], 'saved' => $id]);
        } catch (PDOException $e) {
            if ($db->inTransaction())
                $db->rollBack();
            if ($e->getCode() === '23000') {
                $this->listPage($this->common(), $input['maKyTuyenSinh'], null, '', $id ? 'edit' : 'create', array_merge($input, ['maPhanCong' => $id]), null, 'Phân công bị trùng hoặc không còn hợp lệ. Vui lòng kiểm tra lại.');
                return;
            }
            throw $e;
        } catch (\Throwable $e) {
            if ($db->inTransaction())
                $db->rollBack();
            throw $e;
        }
    }

    public function delete(): void
    {
        $id = Security::intId($_POST['maPhanCong'] ?? null);
        $existing = $id ? $this->model->find($id) : null;
        if (!$existing) {
            View::flash('Không tìm thấy phân công cần xóa.', 'error');
            View::redirect('phancong');
        }
        $db = $this->model->db();
        try {
            $db->beginTransaction();
            $this->model->delete($id, (string) $_SESSION['user']['id']);
            $db->commit();
            View::flash('Xóa phân công thành công.');
        } catch (PDOException $e) {
            if ($db->inTransaction())
                $db->rollBack();
            if ($e->getCode() === '23000')
                View::flash('Không thể xóa phân công vì dữ liệu đang được sử dụng.', 'error');
            else
                throw $e;
        }
        View::redirect('phancong', ['year' => (int) $existing['maKyTuyenSinh']]);
    }

    public function importPage(array $data = []): void
    {
        View::render('phancong/import', array_merge($this->common(), [
            'pageTitle' => 'Nhập Excel hội đồng chấm thi',
            'year' => $this->chosenYear(),
            'preview' => $_SESSION['import_preview'] ?? null,
            'error' => null
        ], $data));
    }

    public function importPreview(): void
    {
        unset($_SESSION['import_preview']);
        $year = Security::intId($_POST['year'] ?? null);
        $years = array_column($this->model->years(), 'maKyTuyenSinh');
        if ($year === null || !in_array($year, array_map('intval', $years), true)) {
            $this->importPage(['error' => 'Kỳ tuyển sinh không hợp lệ.']);
            return;
        }
        if (
            !isset($_FILES['excel']) || $_FILES['excel']['error'] !== UPLOAD_ERR_OK ||
            $_FILES['excel']['size'] > 2_000_000 || $_FILES['excel']['size'] === 0 || !is_uploaded_file($_FILES['excel']['tmp_name'])
        ) {
            $this->importPage(['year' => $year, 'error' => 'Vui lòng tải file Excel/CSV không quá 2 MB.']);
            return;
        }
        try {
            $rows = (new ExcelReader())->read($_FILES['excel']['tmp_name'], basename((string) $_FILES['excel']['name']));
            $items = $this->preparePreview($rows, $year);
            if (!$items)
                throw new \RuntimeException('File không có dữ liệu.');
            $token = bin2hex(random_bytes(16));
            $_SESSION['import_preview'] = ['year' => $year, 'items' => $items, 'token' => $token, 'created' => time()];
            $this->importPage(['year' => $year, 'preview' => $_SESSION['import_preview']]);
        } catch (\Throwable $e) {
            $this->importPage(['year' => $year, 'error' => $e->getMessage(), 'preview' => null]);
        }
    }

    private function preparePreview(array $rows, int $year): array
    {
        if (count($rows) > 501)
            throw new \RuntimeException('Chỉ hỗ trợ tối đa 500 dòng.');
        $items = [];
        $seen = [];
        foreach ($rows as $index => $columns) {
            $originalCount = count($columns);
            $columns = array_pad($columns, 6, '');
            if ($index === 0 && strtolower(trim((string) $columns[0])) === 'maphancong')
                continue;
            if (count(array_filter($columns, static fn($x) => trim((string) $x) !== '')) === 0)
                continue;
            $op = strtoupper(str_replace([' ', '_', '-'], '', trim((string) $columns[5])));
            $id = Security::intId($columns[0]);
            $row = [
                'maKyTuyenSinh' => Security::intId($columns[1]) ?? 0,
                'maNhanSu' => trim((string) $columns[2]),
                'maMonThi' => Security::intId($columns[3]) ?? 0,
                'maPhongThi' => trim((string) $columns[4])
            ];
            $error = null;
            if ($originalCount !== 6)
                $error = 'File phải có đúng 6 cột.';
            elseif ($row['maKyTuyenSinh'] !== $year)
                $error = 'Không đúng kỳ tuyển sinh đang chọn.';
            elseif (!in_array($op, ['THEM', 'CAPNHAT', 'XOA'], true))
                $error = 'Thao tác phải là THEM, CAPNHAT hoặc XOA.';
            elseif ($op === 'THEM' && trim((string) $columns[0]) !== '')
                $error = 'Để trống mã phân công khi Thêm.';
            elseif ($op !== 'THEM' && $id === null)
                $error = 'Cập nhật/Xóa phải có mã phân công.';
            elseif ($op !== 'THEM') {
                $existing = $this->model->find($id);
                if (!$existing || (int) $existing['maKyTuyenSinh'] !== $year)
                    $error = 'Không tìm thấy phân công thuộc kỳ này.';
                elseif (isset($seen['id:' . $id]))
                    $error = 'Một phân công chỉ được thao tác một lần trong file.';
                else
                    $seen['id:' . $id] = true;
            }
            if ($error === null && $op !== 'XOA')
                $error = $this->model->validationError($row, $op === 'CAPNHAT' ? $id : null);
            if ($error === null && $op === 'THEM') {
                $key = implode('|', [$row['maKyTuyenSinh'], $row['maNhanSu'], $row['maMonThi'], $row['maPhongThi']]);
                if (isset($seen['add:' . $key]))
                    $error = 'Trùng với dòng Thêm khác trong file.';
                $seen['add:' . $key] = true;
            }
            if (strlen($row['maNhanSu']) > 20 || strlen($row['maPhongThi']) > 20)
                $error = 'Mã giáo viên/phòng thi quá dài.';
            $items[] = ['line' => $index + 1, 'action' => $op, 'id' => $id, 'data' => $row, 'error' => $error];
            if (count($items) > 500)
                throw new \RuntimeException('Chỉ hỗ trợ tối đa 500 dòng dữ liệu.');
        }
        return $items;
    }

    public function importConfirm(): void
    {
        $preview = $_SESSION['import_preview'] ?? null;
        unset($_SESSION['import_preview']);
        if (
            !$preview || !hash_equals($preview['token'], (string) ($_POST['preview_token'] ?? '')) ||
            time() - $preview['created'] > 900
        ) {
            View::flash('Bản xem trước đã hết hạn. Vui lòng tải lại file.', 'error');
            View::redirect('import');
        }
        foreach ($preview['items'] as $item) {
            if ($item['error'] !== null) {
                View::flash('File còn dòng lỗi, không thể lưu.', 'error');
                View::redirect('import', ['year' => $preview['year']]);
            }
        }
        $db = $this->model->db();
        try {
            $db->beginTransaction();
            foreach ($preview['items'] as $item) {
                $data = $item['data'];
                $id = $item['id'];
                $action = $item['action'];
                if ($action === 'THEM') {
                    $err = $this->model->validationError($data);
                    if ($err !== null)
                        throw new \RuntimeException('Dòng ' . $item['line'] . ': ' . $err);
                    $this->model->create($data, (string) $_SESSION['user']['id']);
                } elseif ($action === 'CAPNHAT') {
                    $existing = $this->model->find($id);
                    if (!$existing || (int) $existing['maKyTuyenSinh'] !== (int) $preview['year'])
                        throw new \RuntimeException('Dòng ' . $item['line'] . ': Phân công đã thay đổi.');
                    $err = $this->model->validationError($data, $id);
                    if ($err !== null)
                        throw new \RuntimeException('Dòng ' . $item['line'] . ': ' . $err);
                    $this->model->update($id, $data, (string) $_SESSION['user']['id']);
                } else {
                    $existing = $this->model->find($id);
                    if (!$existing || (int) $existing['maKyTuyenSinh'] !== (int) $preview['year'])
                        throw new \RuntimeException('Dòng ' . $item['line'] . ': Phân công đã thay đổi.');
                    $this->model->delete($id, (string) $_SESSION['user']['id']);
                }
            }
            $db->commit();
            View::flash('Đã xử lý ' . $this->countItems($preview['items']) . ' dòng Excel thành công.');
            View::redirect('phancong', ['year' => $preview['year']]);
        } catch (\Throwable $e) {
            if ($db->inTransaction())
                $db->rollBack();
            View::flash('Không lưu dữ liệu: ' . ($e instanceof PDOException ? 'Có phân công trùng hoặc dữ liệu liên kết không hợp lệ.' : $e->getMessage()), 'error');
            View::redirect('import', ['year' => $preview['year']]);
        }
    }

    private function countItems(array $items): int
    {
        return count($items);
    }
}
