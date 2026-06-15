<?php

namespace App\Http\Controllers;

use App\Models\Artist;

class DatabaseController extends Controller
{
    public function index()
    {
        $artists = Artist::where('visible', 1)->whereHas('songs')->orderBy('id', 'asc')->get();
        return view('database.index', compact('artists'));
    }

    public function show($artistId)
    {
        $artist = Artist::findOrFail($artistId);
        $bios = $artist->bios()->get();
        return view('database.artist', compact('artist', 'bios'));
    }
}
