
<?php
require_once __DIR__ . '/../model/mHoSo.php';

// Đọc dữ liệu từ file CSV hoặc XLSX
function docBang(string $file, string $ext): array
{
    // Đọc file CSV
    if ($ext === 'csv') {
        $f = fopen($file, 'r');

        if ($f === false) {
            throw new RuntimeException('Không đọc được file CSV.');
        }

        $head = fgetcsv($f, 0, ',');
        $sep = ',';

        // Hỗ trợ CSV dùng dấu chấm phẩy
        if ($head && count($head) === 1) {
            rewind($f);
            $head = fgetcsv($f, 0, ';');
            $sep = ';';
        }

        $rows = [];

        while (($row = fgetcsv($f, 0, $sep)) !== false) {
            if ($row !== [null]) {
                $rows[] = $row;
            }
        }

        fclose($f);

        return [$head, $rows];
    }

    // Chỉ chấp nhận Excel .xlsx
    if ($ext !== 'xlsx') {
        throw new RuntimeException(
            'Chỉ hỗ trợ file .csv hoặc .xlsx.'
        );
    }

    // Kiểm tra extension PHP
    if (
        !class_exists('ZipArchive') ||
        !function_exists('simplexml_load_string')
    ) {
        throw new RuntimeException(
            'PHP cần bật ZipArchive và SimpleXML để đọc Excel.'
        );
    }

    $zip = new ZipArchive();

    if ($zip->open($file) !== true) {
        throw new RuntimeException(
            'Không mở được file Excel .xlsx.'
        );
    }

    try {
        $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

        // Đọc shared strings của Excel
        $shared = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');

        if ($sharedXml !== false) {
            $xml = simplexml_load_string($sharedXml);

            if ($xml === false) {
                throw new RuntimeException(
                    'Dữ liệu chuỗi trong Excel không hợp lệ.'
                );
            }

            foreach ($xml->children($ns)->si as $si) {
                $si->registerXPathNamespace('x', $ns);

                $parts = $si->xpath('.//x:t') ?: [];

                $shared[] = implode(
                    '',
                    array_map(
                        fn($part) => (string)$part,
                        $parts
                    )
                );
            }
        }

        // Đọc sheet đầu tiên
        $sheetXml = $zip->getFromName(
            'xl/worksheets/sheet1.xml'
        );

        if ($sheetXml === false) {
            throw new RuntimeException(
                'Không tìm thấy sheet1 trong file Excel.'
            );
        }

        $xml = simplexml_load_string($sheetXml);

        if ($xml === false) {
            throw new RuntimeException(
                'Nội dung file Excel không hợp lệ.'
            );
        }


        // Đọc các dòng dữ liệu trong Excel bằng XPath
        $all = [];

        $xml->registerXPathNamespace('x', $ns);

        $excelRows = $xml->xpath(
            '//x:sheetData/x:row'
        ) ?: [];

        foreach ($excelRows as $row) {

            $vals = [];

            $row->registerXPathNamespace('x', $ns);

            // Lấy tất cả ô trong dòng
            $cells = $row->xpath('./x:c') ?: [];

            foreach ($cells as $cell) {
                // Xác định vị trí cột A, B, C...
                $ref = (string)$cell['r'];
                $type = (string)$cell['t'];

                if (!preg_match('/^([A-Z]+)/i', $ref, $match)) {
                    continue;
                }

                $col = 0;

                foreach (str_split(strtoupper($match[1])) as $letter) {
                    $col = $col * 26 + ord($letter) - 64;
                }

                $col--;

                $children = $cell->children($ns);
                $value = (string)$children->v;

                // Chuỗi lưu trong sharedStrings.xml
                if ($type === 's') {
                    $value = $shared[(int)$value] ?? '';
                }

                // Chuỗi lưu trực tiếp trong ô Excel
                elseif ($type === 'inlineStr') {
                    $children->is->registerXPathNamespace('x', $ns);

                    $parts = $children->is->xpath('.//x:t') ?: [];

                    $value = implode(
                        '',
                        array_map(
                            fn($part) => (string)$part,
                            $parts
                        )
                    );
                }

                // Cột C = ngày sinh
                // Chuyển ngày dạng số Excel sang YYYY-MM-DD
                if (
                    $col === 2 &&
                    $value !== '' &&
                    is_numeric($value)
                ) {
                    $days = (int)floor((float)$value);

                    if ($days > 0 && $days < 2958466) {
                        $value = (
                            new DateTimeImmutable('1899-12-30')
                        )
                            ->modify('+' . $days . ' days')
                            ->format('Y-m-d');
                    }
                }

                $vals[$col] = $value;
            }

            if (empty($vals)) {
                continue;
            }

            // Bổ sung các ô trống để không lệch cột
            $last = max(array_keys($vals));

            $all[] = array_map(
                fn($i) => $vals[$i] ?? '',
                range(0, $last)
            );
        }

        if (empty($all)) {
            throw new RuntimeException(
                'File Excel không có dữ liệu.'
            );
        }

        return [array_shift($all), $all];
    } finally {
        $zip->close();
    }
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
        if (
            $r['soDienThoai'] !== '' &&
            !preg_match('/^0[0-9]{9}$/D', $r['soDienThoai'])
        ) {
            $errors[] = 'Số điện thoại không hợp lệ';
        }

        if (
            $r['email'] !== '' &&
            !filter_var($r['email'], FILTER_VALIDATE_EMAIL)
        ) {
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
