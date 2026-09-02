<?php

require __DIR__.'/../vendor/autoload.php';

use App\Support\Excel\SpreadsheetReader;
use Illuminate\Http\UploadedFile;

$path = $argv[1] ?? '';
if ($path === '' || ! is_file($path)) {
    fwrite(STDERR, "Usage: php peek_xls.php <file>\n");
    exit(1);
}

$upload = new UploadedFile($path, basename($path), null, null, true);
$rows = SpreadsheetReader::rows($upload);

echo 'Rows: '.count($rows).PHP_EOL;
foreach (array_slice($rows, 0, 20) as $i => $row) {
    echo ($i + 1).': '.json_encode($row, JSON_UNESCAPED_UNICODE).PHP_EOL;
}
