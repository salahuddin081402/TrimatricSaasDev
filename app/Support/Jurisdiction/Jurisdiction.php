<?php

namespace App\Support\Jurisdiction;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\SuperAdmin\UserManagement\User;
use App\Models\SuperAdmin\GlobalSetup\District;
use App\Models\SuperAdmin\GlobalSetup\ClusterMaster;
use App\Models\SuperAdmin\GlobalSetup\ClusterMember;

class Jurisdiction
{
    /**
     * Compute the user's jurisdiction using:
     * - global roles (Super Admin, HO roles) → global
     * - company_division_admins             → division
     * - company_district_admins             → district
     * - cluster_masters.cluster_supervisor  → cluster
     * - cluster_members                     → cluster
     * Everyone else → none
     */
    public function forUser(User $user): JurisdictionContext
    {
        // Ensure role relation is available
        $user->loadMissing('role');

        $companyId = $user->company_id;

        // ---- Role matching config ----
        $cfg       = config('rbac') ?? [];
        $matchMode = $cfg['match'] ?? 'slug'; // 'slug' | 'name'

        // Normalize current role
        $roleName = trim((string) optional($user->role)->name);
        $roleSlug = Str::slug($roleName);

        // Apply alias (if any)
        if (!empty($cfg['aliases_by_slug'][$roleSlug])) {
            $roleSlug = (string) $cfg['aliases_by_slug'][$roleSlug];
        }

        // Name normalizer for legacy lists
        $normName = fn(string $s) => Str::of($s)->lower()->replaceMatches('/[^a-z0-9]+/', '')->value();
        $needle   = $normName($roleName);

        // Helpers: check membership by slug or by normalized name
        $inSlug = function (string $key) use ($cfg, $roleSlug): bool {
            return in_array($roleSlug, $cfg['groups_by_slug'][$key] ?? [], true);
        };
        $inName = function (string $key) use ($cfg, $needle, $normName): bool {
            foreach (($cfg[$key] ?? []) as $label) {
                if ($normName((string)$label) === $needle) return true;
            }
            return false;
        };

        // Group checks (prefer slug sets if configured)
        $isGlobal   = $matchMode === 'slug' ? $inSlug('global')             : $inName('global_roles');
        $isDivAdmin = $matchMode === 'slug' ? $inSlug('division_admin')     : $inName('division_admin_roles');
        $isDisAdmin = $matchMode === 'slug' ? $inSlug('district_admin')     : $inName('district_admin_roles');
        $isCluSup   = $matchMode === 'slug' ? $inSlug('cluster_supervisor') : $inName('cluster_supervisor_roles');
        $isCluMem   = $matchMode === 'slug' ? $inSlug('cluster_member')     : $inName('cluster_member_roles');

        // 1) Global roles
        if ($isGlobal) {
            return JurisdictionContext::global($companyId);
        }

        // 2) Division Admin (DB table, no model needed)
        if ($isDivAdmin) {
            $row = DB::table('company_division_admins')
                ->where('company_id', $companyId)
                ->where('user_id', $user->id)
                ->where('status', 1)
                ->first(['division_id']);

            return $row
                ? JurisdictionContext::division((int)$row->division_id, $companyId)
                : JurisdictionContext::none()->withCompany($companyId);
        }

        // 3) District Admin
        if ($isDisAdmin) {
            $row = DB::table('company_district_admins')
                ->where('company_id', $companyId)
                ->where('user_id', $user->id)
                ->where('status', 1)
                ->first(['district_id']);

            if ($row) {
                $divisionId = District::where('id', $row->district_id)->value('division_id');
                return JurisdictionContext::district((int)$row->district_id, (int)$divisionId, $companyId);
            }
            return JurisdictionContext::none()->withCompany($companyId);
        }

        // 4) Cluster Admin (cluster supervisor)
        if ($isCluSup) {
            $cluster = ClusterMaster::where('company_id', $companyId)
                ->where('cluster_supervisor_id', $user->id)
                ->first(['id', 'district_id']);

            if ($cluster) {
                $divisionId = District::where('id', $cluster->district_id)->value('division_id');
                return JurisdictionContext::cluster((int)$cluster->id, (int)$cluster->district_id, (int)$divisionId, $companyId);
            }
            return JurisdictionContext::none()->withCompany($companyId);
        }

        // 5) Cluster Member (via cluster_members)
        if ($isCluMem) {
            $mem = ClusterMember::where('company_id', $companyId)
                ->where('user_id', $user->id)
                ->first(['cluster_id']);

            if ($mem) {
                $cluster = ClusterMaster::where('company_id', $companyId)
                    ->where('id', $mem->cluster_id)
                    ->first(['id', 'district_id']);
                if ($cluster) {
                    $divisionId = District::where('id', $cluster->district_id)->value('division_id');
                    return JurisdictionContext::cluster((int)$cluster->id, (int)$cluster->district_id, (int)$divisionId, $companyId);
                }
            }
            return JurisdictionContext::none()->withCompany($companyId);
        }

        // 6) Everyone else (Client, Guest, Professional…)
        return JurisdictionContext::none()->withCompany($companyId);
    }
}
