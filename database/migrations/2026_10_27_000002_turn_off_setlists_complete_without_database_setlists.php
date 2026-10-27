<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// 「全セットリスト登録済」を足したとき（2026_10_25_000004）、今あるアーティストを全部印ありにした。
// データベースにセットリストが1件もないアーティストは、印を外す
return new class extends Migration
{
    public function up(): void
    {
        DB::table('artists')
            ->whereNotExists(fn ($q) => $q->selectRaw('1')
                ->from('db_setlists')
                ->join('db_concerts', 'db_concerts.id', '=', 'db_setlists.tour_id')
                ->whereColumn('db_concerts.artist_id', 'artists.id'))
            ->update(['setlists_complete' => false]);
    }

    public function down(): void
    {
    }
};
