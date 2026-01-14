<?php

namespace App\Models\SuperAdmin\GlobalSetup;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Support\Jurisdiction\Jurisdiction;
use App\Models\SuperAdmin\UserManagement\User;
use App\Models\SuperAdmin\GlobalSetup\Company;
use App\Models\SuperAdmin\GlobalSetup\ClusterMaster;

class ClusterMember extends Model
{
    public $timestamps = true;

    protected $table = 'cluster_members';

    protected $fillable = [
        'company_id',   // ⬅️ table is company-scoped
        'cluster_id',
        'user_id',
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

    public function cluster()
    {
        return $this->belongsTo(ClusterMaster::class, 'cluster_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ----------------- Scopes -----------------

    /**
     * Limit members to the user's jurisdiction (and tenant).
     * - Always filters by company_id from context when present
     * - Global    => all members within company
     * - Division  => members whose clusters belong to districts under the division
     * - District  => members whose clusters belong to that district
     * - Cluster   => members of that cluster
     * - None      => none
     */
    public function scopeWithinJurisdiction(Builder $q, User $user): Builder
    {
        $ctx = app(Jurisdiction::class)->forUser($user);

        if (!empty($ctx->company_id)) {
            $q->where('company_id', $ctx->company_id);
        }

        return match ($ctx->level) {
            'global'   => $q,

            'division' => $q->whereIn('cluster_id', function ($sub) use ($ctx) {
                $sub->from('cluster_masters')
                    ->select('id')
                    ->where('company_id', $ctx->company_id)
                    ->whereIn('district_id', function ($sub2) use ($ctx) {
                        $sub2->from('districts')
                             ->select('id')
                             ->where('division_id', $ctx->division_id);
                    });
            }),

            'district' => $q->whereIn('cluster_id', function ($sub) use ($ctx) {
                $sub->from('cluster_masters')
                    ->select('id')
                    ->where('company_id', $ctx->company_id)
                    ->where('district_id', $ctx->district_id);
            }),

            'cluster'  => $q->where('cluster_id', $ctx->cluster_id),

            default    => $q->whereRaw('1=0'),
        };
    }
}
