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
use App\Http\Controllers\ExternalAuthController;
use App\Http\Controllers\MyPageController;
use App\Http\Controllers\MyPageHubController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\MyPageStatsController;
use App\Http\Controllers\ManageController;
use App\Http\Controllers\ManageDbSongController;
use App\Http\Controllers\TimelineController;
use App\Http\Controllers\UserConcertController;

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
        Route::get('biography', [BioController::class, 'index'])->name('database.biography');
        Route::get('biography/{year}', [BioController::class, 'show'])->name('database.biography.year');
    });

    // 個別詳細ページ（アーティスト問わず同一URL）
    Route::get('songs/{id}', [DbSongController::class, 'show'])->name('songs.show');
    Route::get('singles/{id}', [DbSingleController::class, 'show'])->name('singles.show');
    Route::get('albums/{id}', [DbAlbumController::class, 'show'])->name('albums.show');
    Route::get('live/{id}', [DbConcertController::class, 'show'])->name('live.show');

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
Route::get('/stats/artist/{id}/stamps', [App\Http\Controllers\StatsController::class, 'getStampBook'])->name('stats.stamps');

// My Page routes (external/fan user accounts, separate from admin users)
Route::prefix('mypage')->name('mypage.')->group(function () {
    Route::middleware('guest:external')->group(function () {
        Route::get('register', [ExternalAuthController::class, 'showRegister'])->name('register');
        Route::post('register', [ExternalAuthController::class, 'register']);
        Route::get('login', [ExternalAuthController::class, 'showLogin'])->name('login');
        Route::post('login', [ExternalAuthController::class, 'login']);
    });

    Route::middleware('auth:external')->group(function () {
        Route::post('logout', [ExternalAuthController::class, 'logout'])->name('logout');

        Route::get('/', [MyPageHubController::class, 'index'])->name('index');
        Route::get('stats', [MyPageController::class, 'index'])->name('stats');
        Route::get('users/{user}/stats', [MyPageController::class, 'show'])->name('users.stats');

        // ユーザー登録アーティストの「データベース」的な閲覧ページ（公式のdatabase.live相当）。
        // 誰が登録したかに関わらず全ツアーが並ぶ、認可制限のない一覧。
        Route::get('artists/{artistId}/live', [UserConcertController::class, 'index'])->name('user_artists.live');

        Route::prefix('timeline')->name('timeline.')->group(function () {
            Route::get('/', [TimelineController::class, 'index'])->name('index');
            Route::post('{attendance}', [TimelineController::class, 'update'])->name('update');
            Route::post('{attendance}/comments', [TimelineController::class, 'storeComment'])->name('comments.store');
            Route::put('comments/{comment}', [TimelineController::class, 'updateComment'])->name('comments.update');
            Route::delete('comments/{comment}', [TimelineController::class, 'destroyComment'])->name('comments.destroy');
        });

        Route::prefix('attendances')->name('attendances.')->group(function () {
            // アーティスト/ツアーIDは "official-{id}" / "user-{id}" / "new" のいずれか
            Route::get('/', [AttendanceController::class, 'index'])->name('index');
            Route::get('create', [AttendanceController::class, 'create'])->name('create');
            Route::post('create/artists', [AttendanceController::class, 'newArtist'])->name('artists.new');
            Route::get('create/artists/{artistId}/tours', [AttendanceController::class, 'tours'])->name('tours')->where('artistId', '(official|user)-[0-9]+|new');
            Route::post('create/artists/{artistId}/tours', [AttendanceController::class, 'newTour'])->name('tours.new')->where('artistId', '(official|user)-[0-9]+|new');
            Route::get('create/tours/{tourId}/setlists', [AttendanceController::class, 'setlists'])->name('setlists')->where('tourId', '(official|user)-[0-9]+');
            Route::post('create/tours/{tourId}/setlists/new-pattern', [AttendanceController::class, 'newSetlistPattern'])->name('setlists.new_pattern')->where('tourId', '(official|user)-[0-9]+');
            Route::get('create/artists/{artistId}/tours/{tourId}/setlist', [AttendanceController::class, 'setlistCreate'])->name('setlist_create')->where(['artistId' => '(official|user)-[0-9]+|new', 'tourId' => '(official|user)-[0-9]+|new']);
            Route::post('create/artists/{artistId}/tours/{tourId}/setlist', [AttendanceController::class, 'setlistConfirm'])->name('setlist_create.confirm')->where(['artistId' => '(official|user)-[0-9]+|new', 'tourId' => '(official|user)-[0-9]+|new']);
            Route::post('create/artists/{artistId}/tours/{tourId}/confirm', [AttendanceController::class, 'setlistStore'])->name('setlist_create.store')->where(['artistId' => '(official|user)-[0-9]+|new', 'tourId' => '(official|user)-[0-9]+|new']);
            Route::get('create/setlists/{setlistId}', [AttendanceController::class, 'form'])->name('form')->where('setlistId', '(official|user)-[0-9]+');
            Route::post('/', [AttendanceController::class, 'store'])->name('store');
            Route::get('{attendance}/edit', [AttendanceController::class, 'edit'])->name('edit');
            Route::put('{attendance}', [AttendanceController::class, 'update'])->name('update');
            Route::delete('{attendance}', [AttendanceController::class, 'destroy'])->name('destroy');
            Route::get('{attendance}', [AttendanceController::class, 'show'])->name('show');
        });

        Route::get('stamp-books', [MyPageStatsController::class, 'index'])->name('stamps.index');
        Route::get('stats/artist/{artistId}', [MyPageStatsController::class, 'artist'])->name('stats.artist');
        Route::get('stats/artist/{artistId}/stamps', [MyPageStatsController::class, 'stamps'])->name('stats.stamps');

        Route::prefix('manage')->name('manage.')->group(function () {
            Route::get('/', [ManageController::class, 'index'])->name('index');
            Route::get('artists/{artistId}', [ManageController::class, 'artist'])->name('artist');
            Route::get('artists/{artistId}/songs', [ManageController::class, 'songs'])->name('songs');
            Route::get('artists/{artistId}/concerts', [ManageController::class, 'concerts'])->name('concerts');
            Route::get('artists/{artistId}/concerts/{concertId}/setlists', [ManageController::class, 'setlists'])->name('setlists');
            Route::post('artists/{artistId}/concerts/{concertId}/edit', [ManageController::class, 'updateConcert'])->name('concerts.update');
            Route::post('artists/{artistId}/concerts/{concertId}/setlists/{setlistId}/edit', [ManageController::class, 'updateSetlist'])->name('setlists.update');
            Route::post('artists/{artistId}/songs', [ManageController::class, 'storeSong'])->name('songs.store');
            Route::post('artists/{artistId}/songs/reorder', [ManageController::class, 'reorderSongs'])->name('songs.reorder');
            Route::post('artists/{artistId}/songs/{songId}/edit', [ManageController::class, 'updateSong'])->name('songs.update');
            // スワイプ削除はfetch()のPOSTで叩くため、DELETEに加えてPOSTでも受け付ける
            Route::match(['post', 'delete'], 'artists/{artistId}/songs/{songId}', [ManageController::class, 'destroySong'])->name('songs.destroy');
            Route::match(['post', 'delete'], 'artists/{artistId}', [ManageController::class, 'destroyArtist'])->name('artists.destroy');
            Route::match(['post', 'delete'], 'artists/{artistId}/concerts/{concertId}', [ManageController::class, 'destroyConcert'])->name('concerts.destroy');
            Route::match(['post', 'delete'], 'artists/{artistId}/concerts/{concertId}/setlists/{setlistId}', [ManageController::class, 'destroySetlist'])->name('setlists.destroy');

            // Yuki本人だけがアクセスできる、公式データベース（db_songs）の直接編集。
            // ManageDbSongController側で毎回isDatabaseManager()を確認する。
            Route::get('database-artists/{artistId}/songs', [ManageDbSongController::class, 'songs'])->name('database_songs');
            Route::post('database-artists/{artistId}/songs', [ManageDbSongController::class, 'storeSong'])->name('database_songs.store');
            Route::post('database-artists/{artistId}/songs/reorder', [ManageDbSongController::class, 'reorderSongs'])->name('database_songs.reorder');
            Route::post('database-artists/{artistId}/songs/{songId}/edit', [ManageDbSongController::class, 'updateSong'])->name('database_songs.update');
            Route::match(['post', 'delete'], 'database-artists/{artistId}/songs/{songId}', [ManageDbSongController::class, 'destroySong'])->name('database_songs.destroy');
        });

        Route::get('settings', [ExternalAuthController::class, 'showSettings'])->name('settings');
        Route::put('settings/profile', [ExternalAuthController::class, 'updateProfile'])->name('settings.profile');
        Route::put('settings/password', [ExternalAuthController::class, 'updatePassword'])->name('settings.password');
    });
});