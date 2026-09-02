<?php

ini_set('memory_limit', '512M');

$urls = [
    'domain' => 'http://bloodfinder.website/api/home',
    'aws_ip' => 'http://16.171.249.18/api/home',
];

foreach ($urls as $label => $url) {
    echo "==== {$label} ====\n";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_USERAGENT => 'RaoahHomeCheck/1.0',
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    echo "code={$code} bytes=".strlen((string) $body)." err={$err}\n";
    $json = json_decode((string) $body, true);
    echo 'json_error='.json_last_error_msg()."\n";
    echo 'top_keys='.implode(',', array_keys(is_array($json) ? $json : []))."\n";
    echo 'success='.json_encode($json['success'] ?? null)." message=".($json['message'] ?? '')."\n";
    $data = $json['data'] ?? null;
    echo 'data_type='.gettype($data)."\n";
    if (is_array($data)) {
        echo 'data_keys='.implode(',', array_keys($data))."\n";
        foreach (['products', 'categories', 'banners', 'offers', 'sections', 'suggested'] as $k) {
            echo $k.'='.(isset($data[$k]) && is_array($data[$k]) ? count($data[$k]) : 0)."\n";
        }
        $methods = $data['store']['payment_methods'] ?? [];
        echo 'payment_methods='.(is_array($methods) ? count($methods) : 0)."\n";
        if (! empty($data['products'][0]['name'])) {
            echo 'first_product='.$data['products'][0]['name']."\n";
            echo 'first_image='.($data['products'][0]['image_url'] ?? '')."\n";
        }
        foreach ($methods as $m) {
            echo 'pay '.$m['id'].' icon='.(($m['icon_url'] ?? '') !== '' ? 'yes' : 'empty')."\n";
        }
        $missing = 0;
        foreach (array_slice($data['products'] ?? [], 0, 30) as $p) {
            foreach (['id', 'name', 'price', 'image_url'] as $field) {
                if (! array_key_exists($field, $p)) {
                    $missing++;
                }
            }
        }
        echo "missing_fields_in_first_30={$missing}\n";
    }
    echo "\n";
}
