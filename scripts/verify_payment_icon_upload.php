<?php

use App\Models\StorePaymentMethod;
use App\Services\Admin\StorePaymentMethodService;
use Illuminate\Http\UploadedFile;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$source = storage_path('app/public/payments/mada.png');
if (! is_file($source)) {
    fwrite(STDERR, "source missing\n");
    exit(1);
}

$tmp = storage_path('app/tmp_upload_mada.png');
copy($source, $tmp);

$file = new UploadedFile($tmp, 'mada-upload.png', 'image/png', null, true);
$service = app(StorePaymentMethodService::class);

$slug = 'test_upload_'.time();
$method = $service->create([
    'slug' => $slug,
    'label' => 'اختبار رفع أيقونة',
    'hint' => 'مؤقت',
    'icon' => 'bi-credit-card',
    'sort_order' => 99,
    'is_active' => false,
], $file);

$path = (string) $method->icon_url;
$exists = is_file(storage_path('app/public/'.$path));
echo ($path !== '' && $exists ? "UPLOAD_OK {$path}\n" : "UPLOAD_FAIL\n");

StorePaymentMethod::query()->where('id', $method->id)->delete();
if ($path !== '' && is_file(storage_path('app/public/'.$path))) {
    unlink(storage_path('app/public/'.$path));
}
@unlink($tmp);
echo "CLEANED\n";
