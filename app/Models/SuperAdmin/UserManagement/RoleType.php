<?php

namespace App\Models\SuperAdmin\UserManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\SuperAdmin\UserManagement\Role;

class RoleType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'created_by',
        'updated_by'
    ];

    public function roles()
    {
        return $this->hasMany(Role::class);
    }
}
