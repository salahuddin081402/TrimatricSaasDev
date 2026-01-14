<?php

namespace App\Models\SuperAdmin\UserManagement;

use Illuminate\Database\Eloquent\Model;

class CompanyDivisionAdmin extends Model
{
    protected $table = 'company_division_admins';
    public $timestamps = true;

    protected $fillable = [
        'company_id','division_id','user_id','status',
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
    public function division() { return $this->belongsTo(\App\Models\SuperAdmin\GlobalSetup\Division::class, 'division_id'); }
}
