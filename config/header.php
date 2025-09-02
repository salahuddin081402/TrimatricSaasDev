<?php
/**
 * My header config: in development I want to preview the menu as ANY user.
 * - Set to a specific user id (e.g., 1 for Super Admin, 2 for CEO, 3 for Zonal Head).
 * - When login is implemented, set to null to use Auth::id() automatically.
 */
// config/header.php
$forced = env('HEADER_FORCE_USER_ID');          // can be "1", "", null
$forced = is_numeric($forced) ? (int) $forced : null;   // empty or invalid => null

return [
    'dev_force_user_id' => $forced,
];
