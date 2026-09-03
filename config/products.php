<?php

return [

    'image' => [
        'size' => (int) env('PRODUCT_IMAGE_SIZE', 800),
        // Optional solid fill when format is jpg/jpeg; PNG output uses a transparent canvas.
        'background' => env('PRODUCT_IMAGE_BACKGROUND', '#F5F5F5'),
        'format' => env('PRODUCT_IMAGE_FORMAT', 'png'),
        'quality' => (int) env('PRODUCT_IMAGE_QUALITY', 82),
    ],

];
