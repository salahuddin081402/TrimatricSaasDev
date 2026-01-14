<?php

namespace App\Models\SuperAdmin\GlobalSetup;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Support\Jurisdiction\Jurisdiction;
use App\Models\SuperAdmin\UserManagement\User;


class Division extends Model
{
    public $timestamps = true;

    protected $fillable = ['name', 'short_code', 'status', 'created_by', 'updated_by'];

    protected $casts = [
        'status' => 'integer',
    ];

    // Relationships
    public function districts()
    {
        return $this->hasMany(District::class, 'division_id');
    }

    /**
     * Limit divisions visible to the user's jurisdiction.
     * - Global  => all divisions
     * - Division/District/Cluster => only the parent division (ctx->division_id)
     * - None    => no rows
     */
    public function scopeWithinJurisdiction(Builder $q, User $user): Builder
    {
        $ctx = app(Jurisdiction::class)->forUser($user);

        if (method_exists($ctx, 'isGlobal') && $ctx->isGlobal()) {
            return $q;
        }

        if ($ctx->division_id) {
            return $q->where('id', $ctx->division_id);
        }

        return $q->whereRaw('1=0');
    }

   
}
