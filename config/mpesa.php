<?php

return [
    'consumer_key' => env('MPESA_CONSUMER_KEY'),
    'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
    'shortcode' => env('MPESA_SHORTCODE'),
    'passkey' => env('MPESA_PASSKEY'),
    'callback_url' => env('MPESA_CALLBACK_URL'),
    'confirmation_url' => env('MPESA_CONFIRMATION_URL'),
    'validation_url' => env('MPESA_VALIDATION_URL'),
    'environment' => env('MPESA_ENVIRONMENT', 'sandbox'),
    'timeout' => (int) env('MPESA_TIMEOUT', 30),
    'base_url' => env('MPESA_ENVIRONMENT', 'sandbox') === 'production'
        ? 'https://api.safaricom.co.ke'
        : 'https://sandbox.safaricom.co.ke',
];
