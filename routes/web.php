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

Route::get('setlists', function () {
    return view('setlists');
});

Route::get('artists', function () {
    return view('artists');
});

Route::get('search', function () {
    return view('search');
});

Route::resource('setlists', 'SetlistController');
Route::resource('artists', 'ArtistController');
Route::resource('search', 'SearchController');

