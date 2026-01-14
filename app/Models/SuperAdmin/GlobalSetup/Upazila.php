<?php

namespace App\Models\SuperAdmin\GlobalSetup;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Support\Jurisdiction\Jurisdiction;
use App\Models\SuperAdmin\UserManagement\User;
use App\Models\SuperAdmin\GlobalSetup\ClusterMaster;
use App\Models\SuperAdmin\GlobalSetup\ClusterUpazilaMapping;

class Upazila extends Model
{
    /** Kinds kept in sync with DB ENUM('UPAZILA','CITY_CORPORATION','POUROSHAVA') */
    public const KIND_UPAZILA          = 'UPAZILA';
    public const KIND_CITY_CORPORATION = 'CITY_CORPORATION';
    public const KIND_POUROSHAVA       = 'POUROSHAVA';

    public $timestamps = true;

    protected $fillable = [
        'district_id',
        'name',
        'upa_no',
        'short_code',
        'status',
        'created_by',
        'updated_by',
        'kind',        // NEW: allow mass-assigning kind when inserting CC/Pourashava
    ];

    protected $casts = [
        'status' => 'integer',
        // keep 'kind' as string (enum stored as string in DB)
        'kind'   => 'string',
    ];

    // ----------------- Relationships -----------------

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    /**
     * Mappings are company-scoped, so an upazila can have many mappings (one per company).
     */
    public function clusterMappings()
    {
        return $this->hasMany(ClusterUpazilaMapping::class, 'upazila_id');
    }

    /**
     * Backward-compatible alias (used to be hasOne).
     * If you previously relied on a single mapping, prefer clustersForCompany($companyId) below.
     */
    public function clusterMapping()
    {
        return $this->clusterMappings();
    }

    /**
     * All clusters (across companies) this upazila is mapped to.
     * Pivot carries company_id; filter by company when needed.
     */
    public function clusters()
    {
        return $this->belongsToMany(
            ClusterMaster::class,
            'cluster_upazila_mappings',
            'upazila_id',
            'cluster_id'
        )->withPivot('company_id');
    }

    /**
     * Convenience: clusters for a specific company.
     */
    public function clustersForCompany(int $companyId)
    {
        return $this->clusters()->wherePivot('company_id', $companyId);
    }

    // ----------------- Scopes -----------------

    /**
     * Limit upazilas to user's jurisdiction.
     * - Global    => all
     * - Division  => those whose district is in the division
     * - District  => those in the district
     * - Cluster   => those mapped to the cluster (mapping table)
     * - None      => none
     */
    public function scopeWithinJurisdiction(Builder $q, User $user): Builder
    {
        $ctx = app(Jurisdiction::class)->forUser($user);

        return match ($ctx->level) {
            'global'   => $q,

            'division' => $q->whereIn('district_id', function ($sub) use ($ctx) {
                $sub->from('districts')
                    ->select('id')
                    ->where('division_id', $ctx->division_id);
            }),

            'district' => $q->where('district_id', $ctx->district_id),

            'cluster'  => $q->whereIn('id', function ($sub) use ($ctx) {
                $sub->from('cluster_upazila_mappings')
                    ->select('upazila_id')
                    ->where('cluster_id', $ctx->cluster_id);
            }),

            default    => $q->whereRaw('1=0'),
        };
    }

    /**
     * Filter by specific kind(s) — supports array input.
     */
    public function scopeKind(Builder $q, string|array $kind): Builder
    {
        return is_array($kind)
            ? $q->whereIn('kind', $kind)
            : $q->where('kind', $kind);
    }

    /** Convenience scopes for dropdowns */
    public function scopeOnlyUpazila(Builder $q): Builder
    {
        return $q->where('kind', self::KIND_UPAZILA);
    }

    public function scopeOnlyCityCorporation(Builder $q): Builder
    {
        return $q->where('kind', self::KIND_CITY_CORPORATION);
    }

    public function scopeOnlyPourashava(Builder $q): Builder
    {
        return $q->where('kind', self::KIND_POUROSHAVA);
    }

    // ----------------- Accessors / Helpers -----------------

    /**
     * Display name that can optionally include kind for UI,
     * without forcing changes in existing views.
     */
    public function getDisplayNameAttribute(): string
    {
        // Keep classic behavior by default; if you want the label,
        // you can render "{$this->name} ({$this->kind_label})"
        return $this->name;
    }

    /**
     * Human-readable label for the kind (use in badges or dropdown groups).
     */
    public function getKindLabelAttribute(): string
    {
        return match ($this->kind) {
            self::KIND_CITY_CORPORATION => 'City Corporation',
            self::KIND_POUROSHAVA       => 'Pourashava',
            default                     => 'Upazila',
        };
    }
}
