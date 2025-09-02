<?php

namespace App\Models\SuperAdmin\UserManagement;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\SuperAdmin\GlobalSetup\Company;
use App\Models\SuperAdmin\UserManagement\Role;
use App\Models\SuperAdmin\UserManagement\ActivityLog;

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

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

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
}
