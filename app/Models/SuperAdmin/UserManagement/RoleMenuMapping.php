<?php

namespace App\Models\SuperAdmin\UserManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\SuperAdmin\UserManagement\Role;
use App\Models\SuperAdmin\UserManagement\Menu;

class RoleMenuMapping extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'role_id',
        'menu_id',
        'access_type',
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
}
