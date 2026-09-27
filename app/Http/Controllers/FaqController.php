<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.faq', ['topics' => Faq::published()->get()->groupBy('topic')]);
    }
}
