<?php
return [
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
    ],

    'check_peak_factor_delay' => env('CHECK_PEAK_FACTOR_DELAY', 60),
    'time_zone_from_env' => env('APP_TIMEZONE', 'Asia/Karachi'),
];
