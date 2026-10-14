<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $news = News::query()
            ->published()
            ->latest('published_at')
            ->limit(10)
            ->get();

        return view('welcome', ['news' => $news]);
    }
}
