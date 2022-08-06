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

Route::resource('setlists', 'SetlistController');
Route::resource('artists', 'ArtistController');
Route::resource('search', 'SearchController');
Route::resource('songs', 'SongController');
Route::resource('albums', 'AlbumController');
Route::resource('singles', 'SingleController');
Route::resource('years', 'YearController');
Route::resource('bios', 'BioController');
Route::resource('tours', 'TourController');
Route::resource('find', 'FindController');
Route::resource('search', 'SearchController');
Route::resource('apbankfes', 'ApbankController');
Route::resource('events', 'EventController');
Route::resource('live', 'LiveController');
Route::resource('news', 'NewsController');
Route::resource('profile', 'ProfileController');
Route::resource('music', 'DiscoController');
Route::resource('single', 'DiscoSingleController');
Route::resource('album', 'DiscoAlbumController');
Route::resource('radio', 'RadioController');
Route::resource('lyrics', 'LyricController');
Route::resource('/', 'HomeController');
