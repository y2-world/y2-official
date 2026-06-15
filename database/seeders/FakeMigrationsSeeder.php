<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FakeMigrationsSeeder extends Seeder
{
    public function run(): void
    {
        $batch = DB::table('migrations')->max('batch') + 1;

        $fakes = [
            '2026_02_07_212420_create_news_table',
            '2026_02_07_212537_create_events_table',
            '2026_02_07_212537_create_profiles_table',
            '2026_02_07_212537_create_songs_table',
            '2026_02_07_212538_create_albums_table',
            '2026_02_07_212538_create_bios_table',
            '2026_02_07_212538_create_singles_table',
            '2026_02_07_212538_create_tours_table',
            '2026_02_07_212539_create_discos_table',
            '2026_02_07_212539_create_lyrics_table',
            '2026_02_07_213028_add_visible_to_artists_table',
        ];

        $count = 0;
        foreach ($fakes as $m) {
            $exists = DB::table('migrations')->where('migration', $m)->exists();
            if (!$exists) {
                DB::table('migrations')->insert(['migration' => $m, 'batch' => $batch]);
                $count++;
            }
        }

        $this->command->info("Faked {$count} migrations.");
    }
}
