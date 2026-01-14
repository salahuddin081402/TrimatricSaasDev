<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SuperAdmin\UserManagement\User;
use App\Models\SuperAdmin\GlobalSetup\Division;
use App\Models\SuperAdmin\GlobalSetup\District;
use App\Models\SuperAdmin\GlobalSetup\ClusterMaster;

class GeoController
{
    /** Resolve current user (auth first, then forced dev user) */
    private function currentUser(): ?User
    {
        $forced = config('header.dev_force_user_id');

        // If dev forced user id is set, ALWAYS use it for this module.
        if (is_numeric($forced)) {
            return (int) $forced;
        }

        return Auth::id();
    }

    public function divisions(Request $r)
    {
        $user = $this->currentUser();
        if (!$user) return response()->json([]);

        return Division::query()
            ->withinJurisdiction($user)
            ->orderBy('short_code')
            ->get(['id', DB::raw("CONCAT(short_code,' – ',name) AS label")]);
    }

    public function districts(Request $r)
    {
        $user = $this->currentUser();
        if (!$user) return response()->json([]);

        $q = District::query()->withinJurisdiction($user);

        // division_id OPTIONAL:
        // - omitted or "__all__" => ALL districts in user's scope
        // - numeric               => only that division's districts
        $div = $r->input('division_id');
        if ($div && $div !== '__all__') {
            $q->where('division_id', (int) $div);
        }

        return $q->orderBy('short_code')
                 ->get(['id', DB::raw("CONCAT(short_code,' – ',name) AS label")]);
    }

    public function clusters(Request $r)
    {
        $user = $this->currentUser();
        if (!$user) return response()->json([]);

        $q = ClusterMaster::query()->withinJurisdiction($user);

        // district_id OPTIONAL:
        // - omitted or "__all__" => ALL clusters in user's scope
        // - numeric               => only that district's clusters
        $dist = $r->input('district_id');
        if ($dist && $dist !== '__all__') {
            $q->where('district_id', (int) $dist);
        }

        return $q->orderBy('short_code')
                 ->get(['id', DB::raw("CONCAT(short_code,' – ',cluster_name) AS label")]);
    }
}
