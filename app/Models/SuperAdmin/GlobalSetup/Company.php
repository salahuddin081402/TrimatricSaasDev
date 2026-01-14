<?php

namespace App\Models\SuperAdmin\GlobalSetup;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;

    protected $table = 'companies';

    protected $fillable = [
        'country_id',
        'name',
        'description',
        'address',
        'contact_no',
        'logo',
        'status',       // <-- added
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status'     => 'integer',   // <-- added
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

   
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

}
