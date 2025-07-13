<?php

return [
    'secret' => env('JWT_SECRET'),
    'algo' => 'HS256', // Algoritma untuk signing JWT
    'ttl' => env('JWT_TTL', 60), // Waktu kedaluwarsa token dalam menit (default: 60 menit)
    'refresh_ttl' => env('JWT_REFRESH_TTL', 20160), // Waktu kedaluwarsa refresh token dalam menit (default: 14 hari)
    'blacklist_enabled' => env('JWT_BLACKLIST_ENABLED', true),
    'blacklist_grace_period' => env('JWT_BLACKLIST_GRACE_PERIOD', 0),
];
