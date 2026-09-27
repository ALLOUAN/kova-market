<?php

use App\Http\Controllers\FaqController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/page/{page:slug}', PageController::class)->name('pages.show');
Route::get('/faq', FaqController::class)->name('faq');
