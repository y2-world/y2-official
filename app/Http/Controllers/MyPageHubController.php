<?php

namespace App\Http\Controllers;

class MyPageHubController extends Controller
{
    // My Pageトップ（ハブ画面）。Timeline / My Statistics / My Stamp Books /
    // Manage My Artists & Setlists への入り口を並べるだけの、状態を持たない画面。
    public function index()
    {
        return view('mypage.hub');
    }
}
