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

Route::get('search', function () {
    return view('search');
});
Route::get('/news', function () {
    return view('news');
});
Route::get('/profile', function () {
    return view('profile');
});
Route::get('/works', function () {
    return view('works');
});
Route::get('/radio', function () {
    return view('radio');
});
Route::get('/music', function () {
    return view('music');
});
Route::get('/music/single', function () {
    return view('single');
});
Route::get('/music/album', function () {
    return view('album');
});
Route::get('/songs', function () {
    return view('songs');
});
Route::get('/albums', function () {
    return view('albums');
});
Route::get('/tours', function () {
    return view('tours');
});

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
Route::resource('apbanks', 'ApbankController');
Route::resource('events', 'EventController');


