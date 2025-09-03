<?php
// config/header.php

// Forced user id for dev/emulation
$forcedUser = env('HEADER_FORCE_USER_ID');
$forcedUser = is_numeric($forcedUser) ? (int) $forcedUser : null;

// Legacy simulator (kept for early testing)
$forcedReg = env('IS_FORCED_REGISTRATION', false);
$forcedReg = filter_var($forcedReg, FILTER_VALIDATE_BOOLEAN);

// Registration resolver lives here too (so no extra config file needed)
return [
    'dev_force_user_id'    => $forcedUser,   // e.g. 1 or null
    'dev_force_registered' => $forcedReg,    // true/false (simulator)

    'registration' => [
        // 'auto' = use DB if table exists, otherwise fall back to env simulator
        // 'db'   = force DB (if table missing, falls back to env)
        // 'env'  = only env simulator
        'source'         => env('REG_SOURCE', 'auto'),

        'table'          => env('REG_TABLE', null),          // e.g. registration_master
        'user_column'    => env('REG_USER_COLUMN', 'user_id'),
        'company_column' => env('REG_COMPANY_COLUMN', null), // optional: company_id
        'status_column'  => env('REG_STATUS_COLUMN', 'status'),
        'status_active'  => env('REG_STATUS_ACTIVE', '1'),
    ],
];
