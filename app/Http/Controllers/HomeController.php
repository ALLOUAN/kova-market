<?php

namespace App\Http\Controllers;

use App\Services\Storefront\HomePageService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(HomePageService $homePage): View
    {
        return view('pages.home', $homePage->data());
    }
}
