<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemCostUpdate extends Model
{
    use HasFactory,SoftDeletes;
    protected $guarded = [
        'updated_at',
        'created_at'
    ];
}
