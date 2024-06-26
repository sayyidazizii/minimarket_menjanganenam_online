<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvtItemPackge extends Model
{
    protected $table        = 'invt_item_packge';
    protected $primaryKey   = 'item_packge_id';
    protected $guarded = [
        'updated_at',
        'created_at'
    ];
    public function category() {
        return $this->belongsTo(InvtItemCategory::class,'item_category_id','item_category_id');
    }
    public function item() {
        return $this->belongsTo(InvtItem::class,'item_id','item_id');
    }
    public function unit() {
        return $this->belongsTo(InvtItemUnit::class,'item_unit_id','item_unit_id');
    }
    public function barcode() {
       return $this->hasMany(InvtItemBarcode::class,'item_packge_id','item_packge_id');
    }
    protected static function booted(): void
    {
        static::updating(function (InvtItemPackge $data) {
            activity()->performedOn($data)->withProperties(['old'=>$data->getOriginal(),'new'=>$data])->log('Updating Item Package');
        });
    }
}
