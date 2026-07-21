<?php

return [
    'api_key' => env('AFRICASTALKING_API_KEY'),
    'username' => env('AFRICASTALKING_USERNAME'),
    'sender_id' => env('AFRICASTALKING_SENDER_ID', 'OCRS'),
    'environment' => env('AFRICASTALKING_ENVIRONMENT', 'sandbox'),
];
