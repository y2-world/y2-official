<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['albums', 'singles', 'tours', 'songs'] as $tableName) {
            if (!Schema::hasColumn($tableName, 'artist_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('artist_id')->nullable()->after('id');
                });
            }
        }

        // 既存データは全部 Mr.Children のもの — ID を名前で検索して使用
        $mrChildren = DB::table('artists')->where('name', 'Mr.Children')->first();
        if ($mrChildren) {
            $id = $mrChildren->id;
            DB::table('albums')->whereNull('artist_id')->update(['artist_id' => $id]);
            DB::table('singles')->whereNull('artist_id')->update(['artist_id' => $id]);
DB::table('tours')->whereNull('artist_id')->update(['artist_id' => $id]);
            DB::table('songs')->whereNull('artist_id')->update(['artist_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('albums', function (Blueprint $table) {
            $table->dropColumn('artist_id');
        });
        Schema::table('singles', function (Blueprint $table) {
            $table->dropColumn('artist_id');
        });
Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn('artist_id');
        });
    }
};
