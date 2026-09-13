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
        if (config('app.env') !== 'production') {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        }

        // 191文字制限はMySQL（utf8mb4でのインデックスキー長制限対策）のためのもの。
        // PostgreSQLにはこの制限がないため、Postgres接続時は標準の255のままにする。
        if (config('database.default') !== 'pgsql') {
            Schema::defaultStringLength(191);
        }
        Paginator::useBootstrap(); // ← Bootstrapで表示したい場合

        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}
