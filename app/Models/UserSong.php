<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSong extends Model
{
    protected $fillable = [
        'user_artist_id',
        'title',
        'sort_order',
    ];

    public function artist()
    {
        return $this->belongsTo(UserArtist::class, 'user_artist_id');
    }

    // この曲（user_songs.id）が実際に演奏されたUserSetlist（setlist/encore列に自分のidを含むもの）を、
    // 演奏日（concert.date1）降順で返す。DbSong::performedTourSetlists()と同じ考え方。
    public function performedTourSetlists()
    {
        $id = $this->id;

        return UserSetlist::whereHas('concert', fn ($q) => $q->where('user_artist_id', $this->user_artist_id))
            ->with('concert')
            ->get()
            ->filter(function (UserSetlist $setlistModel) use ($id) {
                $lists = array_merge($setlistModel->setlist ?? [], $setlistModel->encore ?? []);

                foreach ($lists as $entry) {
                    if (isset($entry['song']) && is_numeric($entry['song']) && (int) $entry['song'] === (int) $id) {
                        return true;
                    }
                }

                return false;
            })
            ->sortByDesc(fn (UserSetlist $setlistModel) => $setlistModel->concert->date1 ?? '')
            ->values();
    }
}
