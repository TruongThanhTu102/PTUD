<?php
declare(strict_types=1);

namespace App\Services;

/** Reads first sheet of ordinary .xlsx files, or UTF-8 CSV. No Composer required. */
final class ExcelReader
{
    /** @return list<array<int, string>> */
    public function read(string $file, string $originalName): array
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($ext === 'csv')
            return $this->readCsv($file);
        if ($ext === 'xlsx')
            return $this->readXlsx($file);
        throw new \InvalidArgumentException('Chỉ chấp nhận file .xlsx hoặc .csv.');
    }

    private function readCsv(string $file): array
    {
        $handle = fopen($file, 'rb');
        if ($handle === false)
            throw new \RuntimeException('Không thể đọc file CSV.');
        $rows = [];
        try {
            $sample = fgets($handle) ?: '';
            rewind($handle);
            $delimiter = substr_count($sample, ';') > substr_count($sample, ',') ? ';' : ',';
            while (($row = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
                if (count($rows) >= 502)
                    throw new \RuntimeException('File vượt quá 500 dòng dữ liệu.');
                $row = array_map(static fn($v) => trim((string) $v), $row);
                if (!$rows && isset($row[0]))
                    $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', $row[0]);
                $rows[] = $row;
            }
        } finally {
            fclose($handle);
        }
        return $rows;
    }

    /** @return array<int, \SimpleXMLElement> */
    private function nodes(\SimpleXMLElement $xml, string $query): array
    {
        // Prefix registration applies to each new SimpleXML node used as an XPath context.
        $xml->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        return $xml->xpath($query) ?: [];
    }

    private function readXlsx(string $file): array
    {
        if (!class_exists('ZipArchive') || !function_exists('simplexml_load_string')) {
            throw new \RuntimeException('PHP cần bật extension zip và simplexml để đọc .xlsx.');
        }
        $zip = new \ZipArchive();
        if ($zip->open($file) !== true)
            throw new \RuntimeException('Không thể mở file Excel.');
        try {
            $sheetName = 'xl/worksheets/sheet1.xml';
            if ($zip->locateName($sheetName) === false)
                throw new \RuntimeException('Excel thiếu trang tính đầu tiên (sheet1).');
            $stat = $zip->statName($sheetName);
            if (($stat['size'] ?? 0) > 8_000_000)
                throw new \RuntimeException('Trang tính quá lớn.');
            $sheetData = $zip->getFromName($sheetName);
            if (!is_string($sheetData))
                throw new \RuntimeException('Không thể đọc nội dung trang tính.');
            $shared = [];
            $sstName = 'xl/sharedStrings.xml';
            if ($zip->locateName($sstName) !== false) {
                $stat = $zip->statName($sstName);
                if (($stat['size'] ?? 0) > 8_000_000)
                    throw new \RuntimeException('Bảng chuỗi Excel quá lớn.');
                $sst = $zip->getFromName($sstName);
                $xml = @simplexml_load_string((string) $sst, 'SimpleXMLElement', LIBXML_NONET);
                if (!$xml)
                    throw new \RuntimeException('Excel có bảng chuỗi không hợp lệ.');
                foreach ($this->nodes($xml, '//m:si') as $si) {
                    $parts = $this->nodes($si, './/m:t');
                    $shared[] = implode('', array_map(static fn($p) => (string) $p, $parts));
                }
            }
            $xml = @simplexml_load_string($sheetData, 'SimpleXMLElement', LIBXML_NONET);
            if (!$xml)
                throw new \RuntimeException('File Excel không hợp lệ.');
            $rows = [];
            foreach ($this->nodes($xml, '//m:sheetData/m:row') as $r) {
                if (count($rows) >= 502)
                    throw new \RuntimeException('File vượt quá 500 dòng dữ liệu.');
                $values = [];
                foreach ($this->nodes($r, './m:c') as $c) {
                    $ref = (string) $c['r'];
                    if (!preg_match('/^([A-Z]+)\d+$/', $ref, $m))
                        continue;
                    $column = 0;
                    foreach (str_split($m[1]) as $ch)
                        $column = $column * 26 + ord($ch) - 64;
                    if ($column < 1 || $column > 6) {
                        $values[6] = 'EXTRA_COLUMN';
                        continue;
                    }
                    $type = (string) $c['t'];
                    if ($type === 'inlineStr') {
                        $parts = $this->nodes($c, './/m:is//m:t');
                        $value = implode('', array_map(static fn($p) => (string) $p, $parts));
                    } else {
                        $v = $this->nodes($c, './m:v');
                        $raw = isset($v[0]) ? (string) $v[0] : '';
                        $value = $type === 's' ? ($shared[(int) $raw] ?? '') : $raw;
                    }
                    $values[$column - 1] = trim($value);
                }
                if ($values) {
                    $row = [];
                    for ($i = 0; $i < (isset($values[6]) ? 7 : 6); $i++)
                        $row[] = $values[$i] ?? '';
                    $rows[] = $row;
                }
            }
            return $rows;
        } finally {
            $zip->close();
        }
    }
}
