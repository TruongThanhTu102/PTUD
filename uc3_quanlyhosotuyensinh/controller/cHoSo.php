
<?php
require_once __DIR__ . '/../model/mHoSo.php';

// Đọc dữ liệu từ file CSV hoặc XLSX
function docBang(string $file, string $ext): array
{
    if ($ext === 'csv') {
        $f = fopen($file, 'r');

        if ($f === false) {
            throw new RuntimeException('Không đọc được file CSV.');
        }

        $head = fgetcsv($f, 0, ',');

        if ($head && count($head) === 1) {
            rewind($f);
            $head = fgetcsv($f, 0, ';');
            $sep = ';';
        } else {
            $sep = ',';
        }

        $rows = [];

        while (($r = fgetcsv($f, 0, $sep)) !== false) {
            $rows[] = $r;
        }

        fclose($f);

        return [$head, $rows];
    }

    if ($ext !== 'xlsx' || !class_exists('ZipArchive')) {
        throw new RuntimeException(
            'Chỉ hỗ trợ CSV hoặc XLSX (cần bật ZipArchive).'
        );
    }

    $z = new ZipArchive();

    if ($z->open($file) !== true) {
        throw new RuntimeException('Không đọc được file XLSX.');
    }

    $shared = [];
    $ss = $z->getFromName('xl/sharedStrings.xml');

    if ($ss !== false) {
        $xml = simplexml_load_string($ss);

        if ($xml === false) {
            $z->close();
            throw new RuntimeException('Dữ liệu Excel không hợp lệ.');
        }

        foreach ($xml->si as $si) {
            $v = '';

            foreach ($si->xpath('.//t') as $t) {
                $v .= (string)$t;
            }

            $shared[] = $v;
        }
    }

    $sheet = $z->getFromName('xl/worksheets/sheet1.xml');
    $z->close();

    if ($sheet === false) {
        throw new RuntimeException('Không tìm thấy sheet1.');
    }

    $xml = simplexml_load_string($sheet);

    if ($xml === false) {
        throw new RuntimeException('Không đọc được dữ liệu Excel.');
    }

    $all = [];

    foreach ($xml->sheetData->row as $row) {
        $vals = [];

        foreach ($row->c as $c) {
            $ref = (string)$c['r'];

            preg_match('/^[A-Z]+/', $ref, $m);

            $col = 0;

            foreach (str_split($m[0] ?? 'A') as $ch) {
                $col = $col * 26 + ord($ch) - 64;
            }

            $val = (string)$c->v;

            if ((string)$c['t'] === 's') {
                $val = $shared[(int)$val] ?? '';
            } elseif ((string)$c['t'] === 'inlineStr') {
                $val = (string)$c->is->t;
            }

            $vals[$col - 1] = $val;
        }

        if ($vals) {
            $last = max(array_keys($vals));

            $all[] = array_map(
                fn($i) => $vals[$i] ?? '',
                range(0, $last)
            );
        }
    }

    return [array_shift($all), $all];
}


// Kiểm tra dữ liệu học sinh trước khi tạo hồ sơ
function xuLyImport(clsMHoSo $model, int $ky, string $user): array
{
    if (
        empty($_FILES['file']['tmp_name']) ||
        ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
    ) {
        throw new RuntimeException('Vui lòng chọn file hợp lệ.');
    }

    if ($_FILES['file']['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('File tối đa 2 MB.');
    }

    $ext = strtolower(
        pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION)
    );

    [$header, $rows] = docBang(
        $_FILES['file']['tmp_name'],
        $ext
    );

    $expect = [
        'maThiSinh',
        'hoTen',
        'ngaySinh',
        'gioiTinh',
        'cccd',
        'maTruong',
        'duDieuKien',
        'soDienThoai',
        'email',
        'diaChi'
    ];

    $header = array_map(
        fn($s) => trim(
            preg_replace('/^\xEF\xBB\xBF/', '', (string)$s)
        ),
        $header ?? []
    );

    if ($header !== $expect) {
        throw new RuntimeException(
            'File không đúng cấu trúc dữ liệu quy định.'
        );
    }

    if (empty($rows)) {
        throw new RuntimeException(
            'File không có dữ liệu học sinh.'
        );
    }

    if (count($rows) > 500) {
        throw new RuntimeException(
            'File có quá nhiều dòng. Vui lòng chia nhỏ để tải lên.'
        );
    }

    $preview = [];
    $seen = [];

    foreach ($rows as $i => $row) {

        $r = array_combine(
            $expect,
            array_pad(array_slice($row, 0, count($expect)), count($expect), '')
        );

        $r = array_map(
            fn($value) => trim((string)$value),
            $r
        );

        $errors = [];

        // Kiểm tra thông tin bắt buộc
        if (
            $r['maThiSinh'] === '' ||
            $r['hoTen'] === '' ||
            $r['ngaySinh'] === '' ||
            $r['maTruong'] === ''
        ) {
            $errors[] = 'Thiếu trường bắt buộc';
        }

        // Kiểm tra ngày sinh
        $parts = [];

        if (
            !preg_match(
                '/^(\d{4})-(\d{2})-(\d{2})$/',
                $r['ngaySinh'],
                $parts
            ) ||
            !checkdate(
                (int)($parts[2] ?? 0),
                (int)($parts[3] ?? 0),
                (int)($parts[1] ?? 0)
            )
        ) {
            $errors[] = 'Ngày sinh không hợp lệ';
        }

        // Kiểm tra giới tính
        if (
            !in_array(
                $r['gioiTinh'],
                ['Nam', 'Nữ', 'Khác'],
                true
            )
        ) {
            $errors[] = 'Giới tính không hợp lệ';
        }

        // Kiểm tra CCCD
        if (
            $r['cccd'] !== '' &&
            !preg_match('/^[0-9]{12}$/D', $r['cccd'])
        ) {
            $errors[] = 'CCCD không hợp lệ';
        }

        // Thông tin liên hệ không bắt buộc nhưng phải đúng định dạng nếu có
        if ($r['soDienThoai'] !== '' &&
            !preg_match('/^0[0-9]{9}$/D', $r['soDienThoai'])) {
            $errors[] = 'Số điện thoại không hợp lệ';
        }

        if ($r['email'] !== '' &&
            !filter_var($r['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email không hợp lệ';
        }

        if (mb_strlen($r['diaChi']) > 255) {
            $errors[] = 'Địa chỉ vượt quá 255 ký tự';
        }

        // Kiểm tra số cột
        if (count($row) !== count($expect)) {
            $errors[] = 'Sai số cột';
        }

        // Kiểm tra điều kiện dự tuyển
        if ($r['duDieuKien'] !== '1') {
            $errors[] = 'Chưa đủ điều kiện';
        }

        // Kiểm tra trường THCS
        if (!$model->truongTonTai($r['maTruong'])) {
            $errors[] = 'Trường THCS không tồn tại';
        }

        // Kiểm tra hồ sơ đã tồn tại
        $exists = (
            $r['maThiSinh'] !== '' &&
            $model->tonTai($r['maThiSinh'], $ky)
        );

        // Kiểm tra học sinh trùng trong file
        if (
            !$exists &&
            $r['maThiSinh'] !== '' &&
            isset($seen[$r['maThiSinh']])
        ) {
            $errors[] = 'Mã học sinh bị trùng trong file';
        }

        // Xác định trạng thái
        if ($exists) {
            $status = 'Đã có hồ sơ';
        } elseif (!empty($errors)) {
            $status = 'Không hợp lệ';
        } else {
            $status = 'Hợp lệ';
        }

        if ($r['maThiSinh'] !== '') {
            $seen[$r['maThiSinh']] = true;
        }

        $preview[] = [
            'dong' => $i + 2,
            'row' => $r,
            'status' => $status,
            'note' => implode('; ', $errors)
        ];
    }

    return $preview;
}


// Controller quản lý hồ sơ tuyển sinh
class clsCHoSo
{
    private clsMHoSo $model;

    public function __construct()
    {
        $this->model = new clsMHoSo();
    }

    // Lấy danh sách kỳ tuyển sinh
    public function dsKy(): array
    {
        return $this->model->dsKy();
    }

    // Lấy danh sách trường THCS
    public function dsTruong(): array
    {
        return $this->model->dsTruong();
    }

    // Lấy danh sách hồ sơ theo kỳ tuyển sinh
    public function dsHoSo(int $ky, string $q = ''): array
    {
        return $this->model->dsHoSo($ky, $q);
    }

    // Lấy thông tin chi tiết hồ sơ
    public function motHoSo(int $id): ?array
    {
        return $this->model->motHoSo($id);
    }

    // Kiểm tra hồ sơ đã tồn tại
    public function tonTai(string $ma, int $ky): bool
    {
        return $this->model->tonTai($ma, $ky);
    }

    // Tạo hồ sơ tuyển sinh
    public function them(array $row, int $ky, string $user): void
    {
        $this->model->them($row, $ky, $user);
    }

    // Cập nhật hồ sơ tuyển sinh
    public function sua(int $id, array $row, string $user): bool
    {
        return $this->model->sua($id, $row, $user);
    }

    // Hủy hồ sơ tuyển sinh
    public function huy(int $id, string $user, string $lyDoHuy): bool
    {
        return $this->model->huy($id, $user, $lyDoHuy);
    }

    // Nhập và kiểm tra dữ liệu học sinh từ Excel
    public function xuLyImport(int $ky, string $user): array
    {
        return xuLyImport($this->model, $ky, $user);
    }
}
