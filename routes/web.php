<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ArtistController;
use App\Http\Controllers\YearController;
use App\Http\Controllers\DbSongController;
use App\Http\Controllers\DbSingleController;
use App\Http\Controllers\DbAlbumController;
use App\Http\Controllers\DbConcertController;
use App\Http\Controllers\BioController;
use App\Http\Controllers\DatabaseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OfficialNewsController;
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

Route::resource('setlists', 'SlSetlistController');
Route::resource('venue', 'VenueController');
Route::get('setlists/songs/{id}', [App\Http\Controllers\SlSongController::class, 'show']);
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
    // アーティスト選択トップ
    Route::get('/', [DatabaseController::class, 'index']);

    // アーティスト別DB（新URL構造）
    Route::prefix('artists/{artistId}')->group(function () {
        Route::get('/', [DatabaseController::class, 'show'])->name('database.artist');
        Route::get('songs', [DbSongController::class, 'index'])->name('database.songs');
        Route::get('singles', [DbSingleController::class, 'index'])->name('database.singles');
        Route::get('albums', [DbAlbumController::class, 'index'])->name('database.albums');
        Route::get('live', [DbConcertController::class, 'index'])->name('database.live');
        Route::get('live/{id}', [DbConcertController::class, 'show'])->name('live.show');
        Route::get('biography', [BioController::class, 'index'])->name('database.biography');
        Route::get('biography/{year}', [BioController::class, 'show'])->name('database.biography.year');
    });

    // 個別詳細ページ（アーティスト問わず同一URL）
    Route::get('songs/{id}', [DbSongController::class, 'show'])->name('songs.show');
    Route::get('singles/{id}', [DbSingleController::class, 'show'])->name('singles.show');
    Route::get('albums/{id}', [DbAlbumController::class, 'show'])->name('albums.show');

    // 旧URL live/{id} → artist_id付きURLへリダイレクト（後方互換）
    Route::get('live/{id}', function ($id) {
        $concert = \App\Models\DbConcert::findOrFail($id);
        return redirect(route('live.show', [$concert->artist_id, $concert->id]), 301);
    });

    // 旧URL → Mr.Children のアーティストIDへリダイレクト（後方互換）
    Route::get('songs', function () {
        $id = \App\Models\Artist::where('name', 'Mr.Children')->value('id');
        return redirect("/database/artists/{$id}/songs");
    });
    Route::get('singles', function () {
        $id = \App\Models\Artist::where('name', 'Mr.Children')->value('id');
        return redirect("/database/artists/{$id}/singles");
    });
    Route::get('albums', function () {
        $id = \App\Models\Artist::where('name', 'Mr.Children')->value('id');
        return redirect("/database/artists/{$id}/albums");
    })->name('albums.index');
    Route::get('live', function () {
        $id = \App\Models\Artist::where('name', 'Mr.Children')->value('id');
        return redirect("/database/artists/{$id}/live");
    });
    Route::get('years/{year}', function ($year) {
        $id = \App\Models\Artist::where('name', 'Mr.Children')->value('id');
        return redirect("/database/artists/{$id}/biography/{$year}");
    });
});


// Route::resource('tours', 'TourController');
// Route::resource('find', 'FindController');
// Route::resource('search', 'SearchController');
// Route::resource('apbankfes', 'ApbankController');
// Route::resource('events', 'EventController');
// Route::resource('live', 'LiveController');
// Route::resource('news', 'NewsController');
Route::resource('profile', 'ProfileController');
Route::resource('music', 'OfficialReleaseController');
// Route::resource('single', 'DiscoSingleController');
// Route::resource('album', 'DiscoAlbumController');
// Route::resource('radio', 'RadioController');
// Route::resource('lyrics', 'LyricController');
Route::resource('/', 'HomeController');
// Route::resource('/home', 'HomeController');
Route::get('/find', [DbSongController::class, 'search']);
Route::get('/find-setlist-song', [App\Http\Controllers\SlSongController::class, 'search']);

// routes/web.php
Route::get('/top/news', [HomeController::class, 'getAllNews'])->name('news.all');
Route::get('/top/music', [HomeController::class, 'getAllMusic'])->name('music.all');
Route::get('/news/{id}', [OfficialNewsController::class, 'show'])->name('news.show');
Route::get('/lyrics/{id}', [LyricController::class, 'show'])->name('lyric.show');

// Statistics routes
Route::get('/stats', [App\Http\Controllers\StatsController::class, 'index'])->name('stats.index');
Route::get('/stats/artist/{id}', [App\Http\Controllers\StatsController::class, 'getArtistTopSongs'])->name('stats.artist');