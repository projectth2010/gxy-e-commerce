<?php

return [
    'mail' => [
        'from' => [
            'address' => env('MAIL_FROM_ADDRESS', 'no-reply@example.com'),
            'name' => env('MAIL_FROM_NAME', 'E-Commerce Platform'),
        ],
    ],
    'subscription' => [
        'trial_reminder_days' => 3,
        'expiry_reminder_days' => 7,
    ],
];
