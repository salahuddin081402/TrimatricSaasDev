<?php

namespace App\Models\SuperAdmin\UserManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\SuperAdmin\UserManagement\MenuAction;
use App\Models\SuperAdmin\UserManagement\RoleMenuActionPermission;

class Action extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'created_by',
        'updated_by'
    ];

    public function menuActions()
    {
        return $this->hasMany(MenuAction::class);
    }

    public function roleMenuActionPermissions()
    {
        return $this->hasMany(RoleMenuActionPermission::class);
    }
}
