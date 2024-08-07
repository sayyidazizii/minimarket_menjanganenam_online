<?php

namespace App\Models;

use App\Helpers\Setting\Setting;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    protected $table        = 'system_user'; 
    protected $primaryKey   = 'user_id';
    
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'password',
        'user_group_id',
        'section_id',
        'full_name',
        'company_id',
        'phone_number',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    // protected $hidden = [
    //     'password',
    //     'remember_token',
    // ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    public function isAdministrator() {
        return $this->user_id===55;
    }
    public function isAdmin() {
        return $this->user_id===55;
    }
    public function canAddSales(){
        return (($this->group()->first()->user_group_level==2)||Setting::get('can-add-sales',false));
    }
    public function group() {
       return $this->hasOne(SystemUserGroup::class,'user_group_id','user_group_id');
    }
    /**
     * Check if user can access this menu
     * @param mixed $menu
     * @param mixed $routeBack
     * @return bool|mixed|\Illuminate\Http\RedirectResponse
     */
    public function canAccess($menu,$routeBack=null){
        $mapping = SystemMenuMapping::with('menu')->where('user_group_level',$this->group()->first()->user_group_level)->whereHas('menu',function($q) use($menu) {
            $q->where('id', $menu);
        })->first();
        if(empty($mapping)){
            if(is_null($routeBack)){
                abort(403);
            }else{
                throw new HttpResponseException(redirect()->route($routeBack));
            }
        }
        return true;
    }
    /**
     * Get User Setting
     *
     * @param [type] $value
     * @return void
     */
    public function setting($value,$default=null) {
        return Setting::get($value,$default);
    }
}
