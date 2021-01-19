<?php

namespace ShopEM\Providers;

use Illuminate\Support\ServiceProvider;

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
        $this->registerValidator();
        $observeModel = config('observe');
        foreach ($observeModel as $value) 
        {
            $model = '\\ShopEM\\Models\\'.$value;
            $modelObj = new $model();
            $modelObj::observe('\\ShopEM\\Observers\\'.$value.'Observer'::class);
        }
    }

    public function registerValidator()
    {
        // 注册用户名验证规则
        $this->app->validator->extend('username', function (...$parameters) {
            return validateUsername($parameters[1]);
        });

        // 注册中国大陆手机号码验证规则
        $this->app->validator->extend('cn_phone', function (...$parameters) {
            return validateChinaPhoneNumber($parameters[1]);
        });
    }
}
