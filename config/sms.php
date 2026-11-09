<?php

return [
    // log = codes are written to storage/logs/laravel.log (testing only). bulksms = real SMS.
    'driver' => env('SMS_DRIVER', 'log'),
    'sender' => env('SMS_SENDER'),

    'bulksms' => [
        'username' => env('BULKSMS_USERNAME'),
        'password' => env('BULKSMS_PASSWORD'),
    ],
];