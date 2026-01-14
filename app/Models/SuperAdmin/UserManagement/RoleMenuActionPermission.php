<?php

namespace App\Models\SuperAdmin\UserManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\SuperAdmin\UserManagement\Role;
use App\Models\SuperAdmin\UserManagement\Menu;
use App\Models\SuperAdmin\UserManagement\Action;

class RoleMenuActionPermission extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'role_id',
        'menu_id',
        'action_id',
        'allowed',
        'created_by',
        'updated_by',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function action()
    {
        return $this->belongsTo(Action::class);
    }
}
