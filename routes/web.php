<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ArtistController;
use App\Http\Controllers\YearController;
use App\Http\Controllers\SongController;
use App\Http\Controllers\SingleController;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\LiveController;
use App\Http\Controllers\BioController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\LyricController;

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
Route::resource('venue', 'VenueController');
Route::get('setlist-songs/{id}', [App\Http\Controllers\SetlistSongController::class, 'show']);
// Route::resource('songs', 'SongController');
// Route::resource('singles', 'SingleController');
Route::prefix('setlists')->group(function () {
    Route::get('artists', [ArtistController::class, 'index']);
    Route::get('artists/{id}', [ArtistController::class, 'show']);
    // 他のアーティスト関連のルートもここに追加する

    Route::get('years', [YearController::class, 'index']);
    Route::get('years/{year}', [YearController::class, 'show']);
    // 他の年関連のルートもここに追加する
});
Route::prefix('database')->group(function () {
    Route::get('songs', [SongController::class, 'index']);
    Route::get('songs/{id}', [SongController::class, 'show'])->name('songs.show');
    // 他のアーティスト関連のルートもここに追加する

    Route::get('singles', [SingleController::class, 'index']);
    Route::get('singles/{id}', [SingleController::class, 'show'])->name('singles.show');
    // 他のアーティスト関連のルートもここに追加する

    Route::get('albums', [AlbumController::class, 'index'])->name('albums.index');
    Route::get('albums/{id}', [AlbumController::class, 'show'])->name('albums.show');
    // 他のアーティスト関連のルートもここに追加する

    Route::get('live', [LiveController::class, 'index']);
    Route::get('live/{id}', [LiveController::class, 'show'])->name('live.show');
    // 他の年関連のルートもここに追加する

    Route::get('/', [BioController::class, 'index']);
    Route::get('years/{year}', [BioController::class, 'show']);
    // 他の年関連のルートもここに追加する
});


// Route::resource('tours', 'TourController');
// Route::resource('find', 'FindController');
// Route::resource('search', 'SearchController');
// Route::resource('apbankfes', 'ApbankController');
// Route::resource('events', 'EventController');
// Route::resource('live', 'LiveController');
// Route::resource('news', 'NewsController');
Route::resource('profile', 'ProfileController');
Route::resource('music', 'DiscoController');
// Route::resource('single', 'DiscoSingleController');
// Route::resource('album', 'DiscoAlbumController');
// Route::resource('radio', 'RadioController');
// Route::resource('lyrics', 'LyricController');
Route::resource('/', 'HomeController');
// Route::resource('/home', 'HomeController');
Route::get('/find', [SongController::class, 'search']);
Route::get('/find-setlist-song', [App\Http\Controllers\SetlistSongController::class, 'search']);

// routes/web.php
Route::get('/top/news', [HomeController::class, 'getAllNews'])->name('news.all');
Route::get('/top/music', [HomeController::class, 'getAllMusic'])->name('music.all');
Route::get('/news/{id}', [NewsController::class, 'show'])->name('news.show');
Route::get('/lyrics/{id}', [LyricController::class, 'show'])->name('lyric.show');