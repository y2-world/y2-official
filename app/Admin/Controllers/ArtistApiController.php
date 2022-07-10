<?php

namespace App\Admin\Controllers;

use App\Artist;
use Illuminate\Http\Request;
use Encore\Admin\Controllers\AdminController;

class ArtistApiController extends AdminController
{
    public function artists(Request $request)
    {
        $q = $request->get('q');
    
        return Artist::where('name', 'like', "%$q%")->paginate(null, ['id', 'name as text']);
    }
}
