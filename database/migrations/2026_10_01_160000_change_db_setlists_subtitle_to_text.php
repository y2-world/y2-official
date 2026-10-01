<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// セトリのパターン名（subtitle）に、長いツアーの公演日をすべて並べると255文字を超えるので、文字数の上限をなくす
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE db_setlists ALTER COLUMN subtitle TYPE text');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE db_setlists ALTER COLUMN subtitle TYPE varchar(255)');
    }
};
