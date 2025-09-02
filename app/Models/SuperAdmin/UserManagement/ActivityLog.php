<?php

namespace App\Models\SuperAdmin\UserManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\SuperAdmin\GlobalSetup\Company;
use App\Models\SuperAdmin\UserManagement\User;

class ActivityLog extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'user_id',
        'action',
        'table_name',
        'row_id',
        'details',
        'ip_address',
        'time_local',
        'time_dhaka',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'time_local' => 'datetime',
        'time_dhaka' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
