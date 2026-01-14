<?php

namespace App\Models\SuperAdmin\GlobalSetup;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Support\Jurisdiction\Jurisdiction;
use App\Models\SuperAdmin\UserManagement\User;

class District extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'division_id',
        'name',
        'dist_no',
        'short_code',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    // Relationships
    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function upazilas()
    {
        return $this->hasMany(Upazila::class, 'district_id');
    }

    public function clusters()
    {
        return $this->hasMany(ClusterMaster::class, 'district_id');
    }

    /**
     * Limit districts visible to the user's jurisdiction.
     * - Global    => all districts
     * - Division  => districts within ctx->division_id
     * - District  => only ctx->district_id
     * - Cluster   => only the parent district of that cluster (ctx->district_id)
     * - None      => no rows
     */
    public function scopeWithinJurisdiction(Builder $q, User $user): Builder
    {
        $ctx = app(Jurisdiction::class)->forUser($user);

        return match (true) {
            method_exists($ctx, 'isGlobal') && $ctx->isGlobal() => $q,
            !empty($ctx->division_id) && empty($ctx->district_id) => $q->where('division_id', $ctx->division_id), // division-level
            !empty($ctx->district_id) => $q->where('id', $ctx->district_id),                                       // district/cluster-level
            default => $q->whereRaw('1=0'),
        };
    }

   
}
