<?php
// config/header.php

/**
 * Dev/Test Header Config
 * - Keeps "forced user" emulation for local/dev
 * - Centralizes registration state resolver switches (DB vs ENV simulator)
 * - No jurisdiction logic here (that will live in JurisdictionService)
 */

// ---- Forced user (dev/emulation) ----
$forcedUser = env('HEADER_FORCE_USER_ID');
$forcedUser = is_numeric($forcedUser) ? (int) $forcedUser : null;

// Legacy simulator for "is user registered?"
$forcedRegRaw = env('IS_FORCED_REGISTRATION', false);
// Accepts: true/false/1/0/"true"/"false"
$forcedReg = filter_var($forcedRegRaw, FILTER_VALIDATE_BOOLEAN);

// ---- Registration resolver switches ----
// REG_SOURCE must be one of: auto | db | env
$regSourceRaw = env('REG_SOURCE', 'auto');
$regSource    = in_array(strtolower((string) $regSourceRaw), ['auto','db','env'], true)
  ? strtolower((string) $regSourceRaw)
  : 'auto';

// Optional table/column hints for DB-based resolver
$regTable          = env('REG_TABLE', null);             // e.g., registration_master
$regUserColumn     = env('REG_USER_COLUMN', 'user_id');
$regCompanyColumn  = env('REG_COMPANY_COLUMN', null);    // optional: company_id
$regStatusColumn   = env('REG_STATUS_COLUMN', 'status');
$regStatusActive   = (string) env('REG_STATUS_ACTIVE', '1'); // treat as string for strict compares

return [
    // Dev emulation
    'dev_force_user_id'    => $forcedUser,   // e.g., 10 or null
    'dev_force_registered' => $forcedReg,    // bool (simulator only)

    // Registration resolver policy
    'registration' => [
        /**
         * 'auto' = prefer DB if table/columns exist; fallback to ENV simulator
         * 'db'   = force DB (if unavailable, fallback to ENV simulator)
         * 'env'  = use ENV simulator only (ignores DB)
         */
        'source'         => $regSource,

        // DB resolver hints (all optional; safe to leave null)
        'table'          => $regTable,           // e.g., registration_master
        'user_column'    => $regUserColumn,      // default: user_id
        'company_column' => $regCompanyColumn,   // default: null (not required)
        'status_column'  => $regStatusColumn,    // default: status
        'status_active'  => $regStatusActive,    // default: '1'
    ],
];
