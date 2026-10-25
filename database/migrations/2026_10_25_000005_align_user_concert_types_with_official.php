<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// マイページのツアーの種類を公式と同じ意味にする（0 ツアー / 1 単発ライブ / 2 イベント）。
// 今までは、イベント以外は全部 1（ツアーか単発ライブか分からないまま）だったので、終了日があって開始日と違うものはツアー（0）にする。
// 新しく作るときの既定もツアー（0）
return new class extends Migration
{
    public function up(): void
    {
        DB::table('user_concerts')->where('type', 1)->whereNotNull('date2')->whereColumn('date2', '!=', 'date1')->update(['type' => 0]);
        DB::statement('ALTER TABLE user_concerts ALTER COLUMN type SET DEFAULT 0');
    }

    public function down(): void
    {
        DB::table('user_concerts')->where('type', 0)->update(['type' => 1]);
        DB::statement('ALTER TABLE user_concerts ALTER COLUMN type SET DEFAULT 1');
    }
};
