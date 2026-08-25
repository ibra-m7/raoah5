<?php

ini_set('memory_limit', '512M');

function fetch(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $json = json_decode((string) $body, true);

    return ['code' => $code, 'json' => is_array($json) ? $json : []];
}

function dumpBanners(string $label, array $payload): void
{
    echo "==== {$label} code={$payload['code']} ====\n";
    $banners = $payload['json']['data']['banners'] ?? null;
    if (! is_array($banners)) {
        echo "no banners key\n\n";
        return;
    }
    echo 'count='.count($banners)."\n";
    foreach ($banners as $b) {
        echo sprintf(
            "#%s active_title=%s image=%s\n",
            $b['id'] ?? '?',
            $b['title'] ?? '',
            $b['image_url'] ?? ''
        );
    }
    echo "\n";
}

dumpBanners('LOCAL', fetch('http://127.0.0.1:8088/api/home'));
dumpBanners('LAN', fetch('http://172.20.2.63:8088/api/home'));
dumpBanners('RENDER', fetch('https://raoah5.onrender.com/api/home'));
