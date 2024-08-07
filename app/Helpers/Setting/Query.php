<?php
namespace App\Helpers\Setting;

use App\Models\User;
use Illuminate\Support\Str;
use App\Models\CompanySetting;
use Illuminate\Support\Facades\Auth;

/**
 * Query For Setting Helper
 * @version 1.0
 */
class Query{

    private $useTryCatch;
    private $value;
    private $key;
    private $type;
    private $company;
    private $user;

    // public function __construct($key) {
    //     // $this->value = $value;
    //     $this->key = $key;
    // }
    public function get($key,$default=null){
        $user = ($this->user??Auth::user());
        $user_id = ($user->user_id??'guest');
        $company_id = ($user->company_id??'guest');
        return (CompanySetting::where('key',$key)
        ->where('type', ($this->type??"user"))
        ->where('user_id',$user_id)
        ->where('company_id',$company_id)
        ->first()->value??$default);
    }
    public function getElq($key){
        $user = ($this->user??Auth::user());
        $user_id = ($user->user_id??'guest');
        $company_id = ($user->company_id??'guest');
        return CompanySetting::where('key',$key)
        ->where('type', ($this->type??"user"))
        ->where('user_id',$user_id)
        ->where('company_id',$company_id)
        ->first();
    }
    public function property($json){
        return CompanySetting::where('key',$json)->first()->value;
    }
    public function branch($branch_id){
        return CompanySetting::where('key',$branch_id)->first()->value;
    }
    public function company($company_id){
        return CompanySetting::where('key',$company_id)->first()->value;
    }
    public function type($type){
        $this->type=$type;
        return $this;
    }
    public function user(User $user){
        $this->user=$user;
        return $this;
    }
    public function set($value,$key=null){
        $user = ($this->user??Auth::user());
        $user_id = ($user->user_id??'guest');
        $company_id = ($user->company_id??'guest');
        return CompanySetting::updateOrCreate([
            'key' => ($key??Str::uuid()),
            'type' => ($this->type??"user"),
            'user_id' => $user_id,
            'company_id' => $company_id
        ],['value' => $value]);
    }
}