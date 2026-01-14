<?php

namespace App\Models\SuperAdmin\GlobalSetup;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Support\Jurisdiction\Jurisdiction;
use App\Models\SuperAdmin\UserManagement\User;
use App\Models\SuperAdmin\GlobalSetup\Company;
use App\Models\SuperAdmin\GlobalSetup\Upazila;
use App\Models\SuperAdmin\GlobalSetup\ClusterUpazilaMapping;
use App\Models\SuperAdmin\GlobalSetup\ClusterMember;

class ClusterMaster extends Model
{
    public $timestamps = true;

    protected $table = 'cluster_masters';

    protected $fillable = [
        'company_id',            // ⬅️ company-scoped table
        'district_id',
        'cluster_name',
        'cluster_no',
        'short_code',
        'cluster_supervisor_id',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    // ----------------- Relationships -----------------

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'cluster_supervisor_id');
    }

    public function mappings()
    {
        return $this->hasMany(ClusterUpazilaMapping::class, 'cluster_id');
    }

    /**
     * Upazilas mapped to this cluster (pivot carries company_id).
     * Use wherePivot('company_id', $companyId) when you need tenant filtering.
     */
    public function upazilas()
    {
        return $this->belongsToMany(
            Upazila::class,
            'cluster_upazila_mappings',
            'cluster_id',
            'upazila_id'
        )->withPivot('company_id');
    }

    /** Membership rows (cluster_members) */
    public function members()
    {
        return $this->hasMany(ClusterMember::class, 'cluster_id');
    }

    /** Direct access to the member users via cluster_members pivot */
    public function memberUsers()
    {
        return $this->belongsToMany(
            User::class,
            'cluster_members',
            'cluster_id',
            'user_id'
        )->withPivot('company_id');
    }

    // ----------------- Scopes -----------------

    /**
     * Limit clusters to user's jurisdiction (and company).
     * - Always applies company_id from context when present
     * - Global   => all clusters within company
     * - Division => clusters in districts under the division
     * - District => clusters in that district
     * - Cluster  => the single cluster
     * - None     => none
     */
    public function scopeWithinJurisdiction(Builder $q, User $user): Builder
    {
        $ctx = app(Jurisdiction::class)->forUser($user);

        if (!empty($ctx->company_id)) {
            $q->where('company_id', $ctx->company_id);
        }

        return match ($ctx->level) {
            'global'   => $q,
            'division' => $q->whereIn('district_id', function ($sub) use ($ctx) {
                $sub->from('districts')
                    ->select('id')
                    ->where('division_id', $ctx->division_id);
            }),
            'district' => $q->where('district_id', $ctx->district_id),
            'cluster'  => $q->where('id', $ctx->cluster_id),
            default    => $q->whereRaw('1=0'),
        };
    }
}
