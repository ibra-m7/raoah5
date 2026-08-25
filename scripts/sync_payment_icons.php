<?php

use App\Models\StorePaymentMethod;
use App\Support\Media;
use Illuminate\Support\Facades\Storage;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$map = [
    'cash' => 'payments/cash.png',
    'mada' => 'payments/mada.png',
    'stc_pay' => 'payments/stc_pay.png',
    'card' => 'payments/card.png',
    'apple_pay' => 'payments/apple_pay.png',
];

$defaults = [
    'apple_pay' => [
        'label' => 'Apple Pay',
        'hint' => 'ادفع عبر Apple Pay — يُؤكد المتجر العملية',
        'icon' => 'bi-phone',
        'sort_order' => 3,
    ],
];

foreach ($map as $slug => $path) {
    if (! Storage::disk('public')->exists($path)) {
        echo "MISSING FILE: {$path}\n";
        continue;
    }

    $row = StorePaymentMethod::query()->where('slug', $slug)->first();
    if (! $row) {
        if (! isset($defaults[$slug])) {
            echo "SKIP missing row: {$slug}\n";
            continue;
        }
        $row = StorePaymentMethod::query()->create([
            'slug' => $slug,
            'label' => $defaults[$slug]['label'],
            'hint' => $defaults[$slug]['hint'],
            'icon' => $defaults[$slug]['icon'],
            'icon_url' => $path,
            'sort_order' => $defaults[$slug]['sort_order'],
            'is_active' => true,
        ]);
        echo "CREATED {$slug}\n";
        continue;
    }

    $row->update(['icon_url' => $path]);
    echo "UPDATED {$slug} => {$path}\n";
}

$rows = StorePaymentMethod::query()->ordered()->get();
foreach ($rows as $m) {
    echo sprintf(
        "%d | %-10s | %-20s | %s | exists=%s | url=%s\n",
        $m->id,
        $m->slug,
        $m->label,
        $m->icon_url ?: '-',
        Storage::disk('public')->exists((string) $m->icon_url) ? 'yes' : 'no',
        Media::url($m->icon_url) ?: '-'
    );
}
