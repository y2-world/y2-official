<?php

namespace App\Admin\Controllers;

use App\Single;
use Illuminate\Http\Request;
use Encore\Admin\Controllers\AdminController;

class AlbumApiController extends AdminController
{
    public function singles(Request $request)
    {
        $q = $request->get('q');
    
        return Single::where('title', 'like', "%$q%")->paginate(null, ['id', 'title as text']);
    }
}
