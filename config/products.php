<?php

return [

    'image' => [
        'size' => (int) env('PRODUCT_IMAGE_SIZE', 800),
        'background' => env('PRODUCT_IMAGE_BACKGROUND', '#F5F5F5'),
        'format' => env('PRODUCT_IMAGE_FORMAT', 'jpg'),
        'quality' => (int) env('PRODUCT_IMAGE_QUALITY', 82),
    ],

];
