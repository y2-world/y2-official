<?php

namespace App\Console\Commands;

use App\Models\DbSetlist;
use App\Models\DbSong;
use Illuminate\Console\Command;

class InspectTour258Setlist extends Command
{
    protected $signature = 'inspect:tour258-setlist {--search-title= : Search for a song title instead}';

    protected $description = 'Debug: show row 1 setlists for tour 258, or search a song title';

    public function handle(): void
    {
        if ($search = $this->option('search-title')) {
            $songs = DbSong::where('title', 'like', "%{$search}%")->get(['id', 'title', 'artist_id']);
            foreach ($songs as $s) {
                $this->line("id={$s->id} title={$s->title} artist_id={$s->artist_id}");
            }
            if ($songs->isEmpty()) {
                $this->line('no matches');
            }
            return;
        }

        $sl = DbSetlist::where('tour_id', 258)->where('row', 1)->orderBy('order_no')->get();
        $songTitles = DbSong::pluck('title', 'id');

        foreach ($sl as $s) {
            $this->line("=== setlist_id={$s->id} order_no={$s->order_no} subtitle={$s->subtitle} ===");
            $items = array_merge($s->setlist ?? [], $s->encore ?? []);
            $n = 0;
            foreach ($items as $item) {
                $isSkipped = !empty($item['is_daily']) || !empty($item['medley']);
                if (!$isSkipped) {
                    $n++;
                }
                $label = $isSkipped ? '  -  ' : '  ' . $n . '  ';
                $songId = $item['song'] ?? '?';
                $songTitle = is_numeric($songId) ? ($songTitles[$songId] ?? 'NOT FOUND') : $songId;
                $altTitle = !empty($item['alternative_title']) ? ' [' . $item['alternative_title'] . ']' : '';
                $uuid = $item['_uuid'] ?? '?';
                $this->line($label . $songId . ':' . $songTitle . $altTitle . ' (uuid=' . $uuid . ')');
            }
        }
    }
}
