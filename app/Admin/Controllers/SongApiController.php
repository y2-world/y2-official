<?php

namespace App\Admin\Controllers;

use App\Models\Song;
use Illuminate\Http\Request;
use Encore\Admin\Controllers\AdminController;

class SongApiController extends AdminController
{
    public function songs(Request $request)
    {
        $q = $request->get('q');
    
        return Song::where('title', 'like', "%$q%")->paginate(null, ['id', 'title as text']);
    }
}
