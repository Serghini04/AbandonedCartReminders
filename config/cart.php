<?php

return [
    // Reminder intervals in minutes (for easy testing)
    'reminder_intervals' => [
        1 => env('CART_REMINDER_1_MINUTES', 2),
        2 => env('CART_REMINDER_2_MINUTES', 5),
        3 => env('CART_REMINDER_3_MINUTES', 10),
    ],
    
    'reminder_enabled' => env('CART_REMINDERS_ENABLED', true),
];
