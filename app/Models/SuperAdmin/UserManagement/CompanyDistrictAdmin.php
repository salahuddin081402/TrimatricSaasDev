<?php

namespace App\Models\SuperAdmin\UserManagement;

use Illuminate\Database\Eloquent\Model;

class CompanyDistrictAdmin extends Model
{
    protected $table = 'company_district_admins';
    public $timestamps = true;

    protected $fillable = [
        'company_id','district_id','user_id','status',
        'activated_at','deactivated_at','activated_by','deactivated_by',
        'created_by','updated_by',
    ];

    protected $casts = [
        'status' => 'integer',
        'activated_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()     { return $this->belongsTo(User::class, 'user_id'); }
    public function company()  { return $this->belongsTo(\App\Models\SuperAdmin\GlobalSetup\Company::class, 'company_id'); }
    public function district() { return $this->belongsTo(\App\Models\SuperAdmin\GlobalSetup\District::class, 'district_id'); }
}
