<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsignmentItem extends Model
{
    protected $table        = 'sales_consignment_item';
    protected $primaryKey   = 'sales_consignment_item_id';
    protected $guarded = [
        'updated_at',
        'created_at',
    ];
}
