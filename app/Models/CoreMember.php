<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoreMember extends Model
{
    // use HasFactory;
    // protected $table = 'core_member';
    // protected $primaryKey = 'member_id';
    // protected $guarded = [
    //     'last_update',
    // ];

    protected $connection = 'mysql2';
    protected $table = 'core_member';
    protected $primaryKey = 'member_id';
    protected $guarded = [
        'last_update',
    ];
    const CREATED_AT = "created_on";
    const UPDATED_AT = "last_update";
}
