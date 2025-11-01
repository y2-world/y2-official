<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Pagination\Paginator;

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
        Paginator::useBootstrap(); // ← Bootstrapで表示したい場合

        // Heroku環境では常にHTTPSを強制
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
            $_SERVER['HTTPS'] = 'on';
        }
    }
}
