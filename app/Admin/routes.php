<?php

use Illuminate\Routing\Router;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
    'as'            => config('admin.route.prefix') . '.',
], function (Router $router) {

    $router->get('/', 'HomeController@index')->name('home');
    $router->resource('setlists', SetlistController::class);
    $router->resource('artists', ArtistController::class);
    $router->resource('songs', SongController::class);
    $router->resource('albums', AlbumController::class);
    $router->resource('festivals', FestivalController::class);
    $router->get("setlists/admin/api/artists", "ArtistApiController@artists");
});
