<?php

namespace App\Admin\Controllers;

use App\Models\Single;
use Illuminate\Http\Request;
use Encore\Admin\Controllers\AdminController;

class SingleApiController extends AdminController
{
    public function singles(Request $request)
    {
        $q = $request->get('q');
    
        return Single::where('title', 'like', "%$q%")->paginate(null, ['id', 'title as text']);
    }
}
