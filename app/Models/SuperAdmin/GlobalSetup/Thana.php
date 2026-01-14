<?php

namespace App\Models\SuperAdmin\GlobalSetup;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\SuperAdmin\UserManagement\User;
use App\Support\Jurisdiction\Jurisdiction;

class Thana extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'district_id',
        'name',
        'thana_no',
        'short_code',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    // Relationships
    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    // Scope: reuse your jurisdiction logic (district-level same as Upazila)
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

            // If you need cluster-based scoping for thanas later, add a join here
            default    => $q->whereRaw('1=0'),
        };
    }
}
