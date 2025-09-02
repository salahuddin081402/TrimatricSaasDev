<?php

namespace App\Models\SuperAdmin\GlobalSetup;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Country extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'short_code', 'created_by', 'updated_by',
    ];

    // Relationships
    public function companies()
    {
        return $this->hasMany(Company::class);
    }
}
