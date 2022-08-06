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
    $router->resource('singles', SingleController::class);
    $router->resource('years', YearController::class);
    $router->resource('bios', BioController::class);
    $router->resource('tours', TourController::class);
    $router->resource('events', EventController::class);
    $router->resource('apbanks', ApbankController::class);
    $router->resource('news', NewsController::class);
    $router->resource('discos', DiscoController::class);
    $router->resource('profiles', ProfileController::class);
    $router->resource('radios', RadioController::class);
    $router->resource('lyrics', LyricController::class);
    $router->get("setlists/admin/api/artists", "ArtistApiController@artists");
    $router->get("songs/admin/api/albums", "AlbumApiController@albums");
    $router->get("songs/admin/api/singles", "SingleApiController@singles");
    $router->get("albums/admin/api/songs", "SongApiController@songs");
});
