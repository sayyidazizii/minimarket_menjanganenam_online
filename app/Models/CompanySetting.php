<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanySetting extends Model
{
    use HasFactory,SoftDeletes;
    
    protected $fillable = [
        'value',
        'key',
        'user_id',
        'properties',
        'type',
        'branch_id',
        'uuid',
    ];
    protected static function booted()
    {
        $userid=Auth::id();
        static::creating(function (CompanySetting $model) use($userid) {
            $model->uuid = Str::uuid();
            $model->created_id = $userid;
        });
        static::updated(function (CompanySetting $model) use($userid) {
            $model->updated_id = $userid;
        });
        static::deleting(function (CompanySetting $model) use($userid) {
            $model->deleted_id = $userid;
        });
    }
}