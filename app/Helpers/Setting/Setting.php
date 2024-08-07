<?php
namespace App\Helpers\Setting;

use App\Models\User;
use Illuminate\Support\Str;
use App\Models\CompanySetting;

class Setting{
    private $type ;
    public static function get($key, $default = null){
        $q= new Query;
        return $q->get($key,$default);
    }
    /**
     * Set setting value
     * @param mixed $value
     * @param mixed $key
     * @return int
     */
    public static function set($value,$key=null){
        $q= new Query;
        return $q->set($value,$key);
    }
    public static function type($type) {
        // content
    }
    public static function company($type) {
        // content
    }
    public static function branch($type) {
        // content
    }
    public static function user(User $user) {
        // content
    }
}