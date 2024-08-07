<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\RedirectResponse;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
        RedirectResponse::macro('msg',function($string){
            return $this->with('msg', $string);
        });
        RedirectResponse::macro('success',function($string){
            return $this->with(['msg'=> $string,'type'=>'success']);
        });
        RedirectResponse::macro('error',function($string){
            return $this->with(['msg'=> $string,'type'=>'danger']);
        });
        RedirectResponse::macro('danger',function($string){
            return $this->with(['msg'=> $string,'type'=>'danger']);
        });
        RedirectResponse::macro('warning',function($string){
            return $this->with(['msg'=> $string,'type'=>'warning']);
        });
        RedirectResponse::macro('info',function($string){
            return $this->with(['msg'=> $string,'type'=>'info']);
        });
    }
    
}
