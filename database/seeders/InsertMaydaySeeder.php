<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InsertMaydaySeeder extends Seeder
{
    public function run(): void
    {
        $bz = DB::table('artists')->where('name', "B'z")->value('id');
        if (!$bz) { $this->command->error("B'z not found"); return; }
        if (DB::table('songs')->where('title', 'Mayday!')->where('artist_id', $bz)->exists()) {
            $this->command->info("Already exists"); return;
        }
        DB::statement('UPDATE songs SET id = id + 1 WHERE artist_id = ? AND id >= 554 ORDER BY id DESC', [$bz]);
        DB::table('songs')->insert(['id' => 554, 'artist_id' => $bz, 'title' => 'Mayday!', 'created_at' => now(), 'updated_at' => now()]);
        $this->command->info('Inserted Mayday! at id=554, TINY DROPS moved to id=' . DB::table('songs')->where('title','TINY DROPS')->where('artist_id',$bz)->value('id'));
    }
}
