<?php
// config/rbac.php

/**
 * RBAC groupings for jurisdiction checks.
 * - Keep original name-based groups (backward-compatible).
 * - Add slug-based groups (preferred) to avoid case/spacing issues.
 * - Provide role_type groupings (optional) and aliases.
 *
 * Usage (recommended in JurisdictionService):
 *   $slug = \Illuminate\Support\Str::slug($user->role->name); // e.g. "head-office-project-manager"
 *   in_array($slug, config('rbac.groups_by_slug.global'), true)
 */

use Illuminate\Support\Str;

// --- Original name-based groups (UNCHANGED) ---
$globalByName = [
    'Super Admin',
    'CEO',
    'Head Office Admin',
    'Head Office Project Manager',
];

$divisionAdminByName = ['Division Admin'];
$districtAdminByName = ['District Admin'];
$clusterSupervisorByName = ['Cluster Admin'];
$clusterMemberByName    = ['Cluster Member'];
$noJurisdictionByName   = ['Client', 'Guest', 'Professional'];

// --- Helper: to slugs (kebab-case) ---
$toSlugs = static function(array $names): array {
    return array_values(array_unique(array_map(static fn($n) => Str::slug($n), $names)));
};

// --- Slug-based groups (PREFERRED) ---
$groupsBySlug = [
    'global'             => $toSlugs($globalByName),            // super-admin, ceo, head-office-admin, head-office-project-manager
    'division_admin'     => $toSlugs($divisionAdminByName),     // division-admin
    'district_admin'     => $toSlugs($districtAdminByName),     // district-admin
    'cluster_supervisor' => $toSlugs($clusterSupervisorByName), // cluster-admin
    'cluster_member'     => $toSlugs($clusterMemberByName),     // cluster-member
    'no_jurisdiction'    => $toSlugs($noJurisdictionByName),    // client, guest, professional
];

// --- Optional: role_type → roles (by slug) ---
$roleTypesBySlug = [
    'super-admin'  => ['super-admin'],
    'head-office'  => $toSlugs(['CEO', 'Head Office Admin', 'Head Office Project Manager']),
    'business-officer' => $toSlugs(['Division Admin', 'District Admin', 'Cluster Admin', 'Cluster Member']),
    'client'       => ['client', 'guest'],
    'professional' => ['professional'],
];

// --- Optional: aliases to normalize common variations (by slug) ---
$aliasesBySlug = [
    // e.g. "ho-project-manager" => "head-office-project-manager",
];

// --- Return full config ---
return [
    // Name-based groups (legacy-compatible)
    'global_roles'            => $globalByName,
    'division_admin_roles'    => $divisionAdminByName,
    'district_admin_roles'    => $districtAdminByName,
    'cluster_supervisor_roles'=> $clusterSupervisorByName,
    'cluster_member_roles'    => $clusterMemberByName,
    'no_jurisdiction_roles'   => $noJurisdictionByName,

    // Slug-based groups (preferred for new code)
    'groups_by_slug'          => $groupsBySlug,

    // Optional helpers
    'role_types_by_slug'      => $roleTypesBySlug,
    'aliases_by_slug'         => $aliasesBySlug,

    // Matching strategy the service should prefer
    'match'                   => 'slug', // 'slug' or 'name'
];
