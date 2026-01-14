<?php

namespace App\Models\SuperAdmin\UserManagement;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\SuperAdmin\GlobalSetup\Company;
use App\Models\SuperAdmin\UserManagement\Role;
use App\Models\SuperAdmin\UserManagement\ActivityLog;
use App\Models\SuperAdmin\GlobalSetup\ClusterMaster;
use App\Models\SuperAdmin\GlobalSetup\ClusterMember;

class User extends Authenticatable
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'role_id',
        'name',
        'email',
        'password',
        'remember_token',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts  = [
        'email_verified_at' => 'datetime',
        'status'            => 'integer',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
        'deleted_at'        => 'datetime',
    ];

    // ----------------- Core relations -----------------

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    // ----------------- Jurisdiction-related (company-scoped) -----------------

    /** Cluster Supervisor (Cluster Admin) – one cluster per user per company */
    public function clusterSupervisorOf()
    {
        return $this->hasOne(ClusterMaster::class, 'cluster_supervisor_id')
                    ->where('company_id', $this->company_id);
    }

    /** Cluster membership row (cluster_members) – one per user per company */
    public function clusterMembership()
    {
        return $this->hasOne(ClusterMember::class, 'user_id')
                    ->where('company_id', $this->company_id);
    }

    /** Direct clusters via cluster_members pivot; filtered to this user's company */
    public function clusters()
    {
        return $this->belongsToMany(
            ClusterMaster::class,
            'cluster_members',
            'user_id',
            'cluster_id'
        )->withPivot('company_id')
         ->wherePivot('company_id', $this->company_id);
    }

  }
