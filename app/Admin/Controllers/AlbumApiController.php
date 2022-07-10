<?php

namespace App\Admin\Controllers;

use App\Album;
use Illuminate\Http\Request;
use Encore\Admin\Controllers\AdminController;

class AlbumApiController extends AdminController
{
    public function albums(Request $request)
    {
        $q = $request->get('q');
    
        return Album::where('title', 'like', "%$q%")->paginate(null, ['id', 'title as text']);
    }
}
