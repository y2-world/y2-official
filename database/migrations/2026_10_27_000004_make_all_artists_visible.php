<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// アーティストの「公開」は、Yuki's Setlists のアーティストの選択欄（Live / Fes）に出すかどうかにする（Live / Fes とは関係ない）。
// 今まで「非公開＝Fes」として使っていたので、一旦全部公開にする
return new class extends Migration
{
    public function up(): void
    {
        DB::table('artists')->where('visible', '!=', 1)->update(['visible' => 1]);
    }

    public function down(): void
    {
    }
};
