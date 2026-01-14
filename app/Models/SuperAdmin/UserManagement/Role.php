<?php

namespace App\Models\SuperAdmin\UserManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\SuperAdmin\GlobalSetup\Company;
use App\Models\SuperAdmin\UserManagement\RoleType;
use App\Models\SuperAdmin\UserManagement\RoleMenuMapping;
use App\Models\SuperAdmin\UserManagement\Menu;
use App\Models\SuperAdmin\UserManagement\RoleMenuActionPermission;
use App\Models\SuperAdmin\UserManagement\User;

class Role extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'role_type_id',
        'name',
        'description',
        'created_by',
        'updated_by',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function roleType()
    {
        return $this->belongsTo(RoleType::class);
    }

    public function roleMenuMappings()
    {
        return $this->hasMany(RoleMenuMapping::class);
    }

    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'role_menu_mappings')
                    ->withTimestamps()
                    ->withPivot(['access_type', 'deleted_at']);
    }

    public function roleMenuActionPermissions()
    {
        return $this->hasMany(RoleMenuActionPermission::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
