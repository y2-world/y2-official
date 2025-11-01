<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FlipVisibleValues extends Command
{
    protected $signature = 'data:flip-visible';
    protected $description = 'Flip visible column values (0<->1) for news, discos, and artists';

    public function handle()
    {
        $this->info('Flipping visible values...');

        DB::statement('UPDATE news SET visible = CASE WHEN visible = 0 THEN 1 WHEN visible = 1 THEN 0 ELSE visible END');
        $this->info('News updated');

        DB::statement('UPDATE discos SET visible = CASE WHEN visible = 0 THEN 1 WHEN visible = 1 THEN 0 ELSE visible END');
        $this->info('Discos updated');

        DB::statement('UPDATE artists SET visible = CASE WHEN visible = 0 THEN 1 WHEN visible = 1 THEN 0 ELSE visible END');
        $this->info('Artists updated');

        $this->info('Done!');
    }
}
