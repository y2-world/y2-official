<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ユーザーがアーティストごとの楽曲一覧を自由に並べ替えられるようにする
        Schema::table('user_songs', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('title');
        });

        // 既存行に、現在のid順をそのままsort_orderとして割り当てる
        $songsByArtist = DB::table('user_songs')->orderBy('id')->get()->groupBy('user_artist_id');
        foreach ($songsByArtist as $artistId => $songs) {
            foreach ($songs->values() as $index => $song) {
                DB::table('user_songs')->where('id', $song->id)->update(['sort_order' => $index]);
            }
        }

        // 削除は作成者本人のみ許可するため、セットリストパターンにも作成者を記録する
        // （user_artists/user_concertsは既にexternal_user_idを持つ）
        Schema::table('user_setlists', function (Blueprint $table) {
            $table->foreignId('external_user_id')->nullable()->after('user_concert_id')
                ->constrained('external_users')->nullOnDelete();
        });

        // 既存のuser_setlists行には、親ツアー（user_concerts）の作成者を引き継がせる。
        // MySQLとPostgreSQLでUPDATE JOINの構文が異なる。
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('
                UPDATE user_setlists
                SET external_user_id = user_concerts.external_user_id
                FROM user_concerts
                WHERE user_setlists.user_concert_id = user_concerts.id
            ');
        } else {
            DB::statement('
                UPDATE user_setlists
                JOIN user_concerts ON user_setlists.user_concert_id = user_concerts.id
                SET user_setlists.external_user_id = user_concerts.external_user_id
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_setlists', function (Blueprint $table) {
            $table->dropConstrainedForeignId('external_user_id');
        });

        Schema::table('user_songs', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
