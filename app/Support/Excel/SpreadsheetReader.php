<?php

namespace App\Support\Excel;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use ZipArchive;

final class SpreadsheetReader
{
    /**
     * @return list<list<string>>
     */
    public static function rows(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
        $path = $file->getRealPath();
        if (! is_string($path) || $path === '') {
            throw new RuntimeException('تعذر قراءة الملف المرفوع.');
        }

        return match ($ext) {
            'csv', 'txt' => self::csv($path),
            'xml', 'xls' => self::spreadsheetMl($path),
            'xlsx' => self::xlsx($path),
            default => throw new RuntimeException('صيغة الملف غير مدعومة. استخدم القالب أو ملف CSV / Excel.'),
        };
    }

    /**
     * @return list<list<string>>
     */
    private static function csv(string $path): array
    {
        $raw = file_get_contents($path);
        if ($raw === false) {
            return [];
        }
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw) ?? $raw;
        $delimiter = substr_count($raw, ';') > substr_count($raw, ',') ? ';' : ',';
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $raw);
        rewind($handle);

        $rows = [];
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = array_map(static fn ($cell) => trim((string) $cell), $data);
        }
        fclose($handle);

        return $rows;
    }

    /**
     * @return list<list<string>>
     */
    private static function spreadsheetMl(string $path): array
    {
        $xml = @simplexml_load_file($path);
        if ($xml === false) {
            $raw = file_get_contents($path) ?: '';
            if (! str_contains($raw, 'ss:Workbook') && ! str_contains($raw, 'Workbook')) {
                return self::csv($path);
            }
            throw new RuntimeException('تعذر قراءة ملف Excel. نزّل القالب من لوحة التحكم واستخدمه.');
        }

        $xml->registerXPathNamespace('ss', 'urn:schemas-microsoft-com:office:spreadsheet');
        $worksheets = $xml->Worksheet ?? $xml->children('urn:schemas-microsoft-com:office:spreadsheet')->Worksheet;
        $target = null;
        foreach ($worksheets as $sheet) {
            $attrs = $sheet->attributes('urn:schemas-microsoft-com:office:spreadsheet')
                ?: $sheet->attributes();
            $name = (string) ($attrs['Name'] ?? '');
            if ($name === 'المنتجات' || strcasecmp($name, 'products') === 0) {
                $target = $sheet;
                break;
            }
        }
        $target ??= $worksheets[0] ?? null;
        if ($target === null) {
            return [];
        }

        $table = $target->Table ?? $target->children('urn:schemas-microsoft-com:office:spreadsheet')->Table;
        $rows = [];
        foreach ($table->Row ?? [] as $row) {
            $cells = [];
            $col = 1;
            foreach ($row->Cell ?? [] as $cell) {
                $attrs = $cell->attributes('urn:schemas-microsoft-com:office:spreadsheet')
                    ?: $cell->attributes();
                if (isset($attrs['Index'])) {
                    $col = (int) $attrs['Index'];
                }
                while (count($cells) < $col - 1) {
                    $cells[] = '';
                }
                $data = $cell->Data ?? $cell->children('urn:schemas-microsoft-com:office:spreadsheet')->Data;
                $cells[] = trim((string) $data);
                $col++;
            }
            $rows[] = $cells;
        }

        return $rows;
    }

    /**
     * @return list<list<string>>
     */
    private static function xlsx(string $path): array
    {
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'xlsx_'.bin2hex(random_bytes(6));
        mkdir($dir, 0777, true);

        try {
            self::extractZip($path, $dir);
            $strings = self::sharedStrings($dir.DIRECTORY_SEPARATOR.'xl'.DIRECTORY_SEPARATOR.'sharedStrings.xml');
            $sheet = self::firstSheetPath($dir);
            if ($sheet === null) {
                throw new RuntimeException('ملف Excel لا يحتوي على ورقة بيانات.');
            }

            return self::xlsxSheet($sheet, $strings);
        } finally {
            self::deleteDir($dir);
        }
    }

    private static function extractZip(string $path, string $dest): void
    {
        if (class_exists(ZipArchive::class)) {
            $zip = new ZipArchive();
            if ($zip->open($path) === true) {
                $zip->extractTo($dest);
                $zip->close();

                return;
            }
        }

        $result = Process::run(['tar', '-xf', $path, '-C', $dest]);
        if (! $result->successful() || ! is_dir($dest.DIRECTORY_SEPARATOR.'xl')) {
            throw new RuntimeException('تعذر فتح ملف .xlsx. احفظ القالب كما هو أو صدّره CSV من Excel ثم ارفعه.');
        }
    }

    /**
     * @return list<string>
     */
    private static function sharedStrings(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }
        $xml = @simplexml_load_file($path);
        if ($xml === false) {
            return [];
        }
        $xml->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $out = [];
        foreach ($xml->si ?? [] as $si) {
            $text = '';
            foreach ($si->t as $t) {
                $text .= (string) $t;
            }
            if ($text === '' && isset($si->r)) {
                foreach ($si->r as $run) {
                    $text .= (string) $run->t;
                }
            }
            $out[] = $text;
        }

        return $out;
    }

    private static function firstSheetPath(string $dir): ?string
    {
        $preferred = [
            $dir.DIRECTORY_SEPARATOR.'xl'.DIRECTORY_SEPARATOR.'worksheets'.DIRECTORY_SEPARATOR.'sheet1.xml',
        ];
        foreach ($preferred as $path) {
            if (is_file($path)) {
                return $path;
            }
        }
        $files = glob($dir.DIRECTORY_SEPARATOR.'xl'.DIRECTORY_SEPARATOR.'worksheets'.DIRECTORY_SEPARATOR.'*.xml') ?: [];

        return $files[0] ?? null;
    }

    /**
     * @param  list<string>  $strings
     * @return list<list<string>>
     */
    private static function xlsxSheet(string $path, array $strings): array
    {
        $xml = @simplexml_load_file($path);
        if ($xml === false) {
            return [];
        }
        $rows = [];
        foreach ($xml->sheetData->row ?? [] as $row) {
            $cells = [];
            foreach ($row->c as $c) {
                $ref = (string) $c['r'];
                $col = self::columnIndex($ref);
                while (count($cells) < $col - 1) {
                    $cells[] = '';
                }
                $type = (string) $c['t'];
                $value = '';
                if ($type === 's') {
                    $idx = (int) $c->v;
                    $value = $strings[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = (string) ($c->is->t ?? '');
                } else {
                    $value = (string) ($c->v ?? '');
                }
                $cells[] = trim($value);
            }
            $rows[] = $cells;
        }

        return $rows;
    }

    private static function columnIndex(string $ref): int
    {
        preg_match('/^([A-Z]+)/i', $ref, $m);
        $letters = strtoupper($m[1] ?? 'A');
        $n = 0;
        foreach (str_split($letters) as $ch) {
            $n = $n * 26 + (ord($ch) - 64);
        }

        return max(1, $n);
    }

    private static function deleteDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$item;
            is_dir($path) ? self::deleteDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
