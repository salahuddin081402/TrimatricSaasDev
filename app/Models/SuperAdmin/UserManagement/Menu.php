<?php

namespace App\Models\SuperAdmin\UserManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\SuperAdmin\UserManagement\Role;
use App\Models\SuperAdmin\UserManagement\MenuAction;
use App\Models\SuperAdmin\UserManagement\RoleMenuActionPermission;

class Menu extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name',
        'uri',
        'icon',
        'menu_order',
        'description',
        'created_by',
        'updated_by',
    ];

    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('menu_order');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_menu_mappings')
                    ->withTimestamps()
                    ->withPivot(['access_type', 'deleted_at']);
    }

    public function menuActions()
    {
        return $this->hasMany(MenuAction::class);
    }

    public function roleMenuActionPermissions()
    {
        return $this->hasMany(RoleMenuActionPermission::class);
    }
}
