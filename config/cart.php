<?php

return [
    // Reminder intervals in hours
    'reminder_intervals' => [
        1 => env('CART_REMINDER_1_HOURS', 1),
        2 => env('CART_REMINDER_2_HOURS', 6),
        3 => env('CART_REMINDER_3_HOURS', 24),
    ],
    
    'reminder_enabled' => env('CART_REMINDERS_ENABLED', true),
];
