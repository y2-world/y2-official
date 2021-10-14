<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('home');
});

Route::get('years/2003', function () {
    return view('years/2003');
});

Route::get('setlists', function () {
    return view('setlists');
});

Route::get('artists', function () {
    return view('artists');
});

Route::get('setlists', 'getSetlistController@index');
Route::resource('setlists', 'getSetlistController');
Route::resource('artists', 'ArtistController');

Route::get('setlists/{id}', 'getSetlistController@show')->name('setlist.show');
