<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consignment extends Model
{
    protected $table        = 'sales_consignment';
    protected $primaryKey   = 'sales_consignment_id';
    protected $guarded = [
        'updated_at',
        'created_at',
    ];
}