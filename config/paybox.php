<?php

return [
    'merchant_id' => env('PAYBOX_MERCHANT_ID', 'https://api.freedompay.money'),
    'secret_key' => env('PAYBOX_SECRET_KEY', 'https://api.freedompay.money'),
    'url' => env('PAYBOX_GATEWAY_URL', 'https://api.freedompay.money'),
    'routes' => [
        'init_payment' => 'init_payment.php',
        'status_payment' => 'get_status2.php',
    ],
    'currency' => env('PAYBOX_CURRENCY', 'KZT'),
    'salt' => 'arstudio',
    'success_callback' => "https://api.arstudio.kz/paybox/success",
    'failure_callback' => "https://api.arstudio.kz/paybox/failure",
];
