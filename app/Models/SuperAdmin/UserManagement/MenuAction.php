<?php

namespace App\Models\SuperAdmin\UserManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\SuperAdmin\UserManagement\Menu;
use App\Models\SuperAdmin\UserManagement\Action;

class MenuAction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'menu_id',
        'action_id',
        'button_label',
        'button_icon',
        'button_order',
        'created_by',
        'updated_by',
    ];

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function action()
    {
        return $this->belongsTo(Action::class);
    }
}
