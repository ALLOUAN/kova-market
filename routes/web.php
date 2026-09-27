<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Catalog: these route names turn the menu, card and brand links from "#" into real URLs.
Route::get('/boutique', [CatalogController::class, 'index'])->name('shop.index');
Route::get('/categorie/{category:slug}', [CatalogController::class, 'category'])->name('categories.show');
Route::get('/marque/{brand:slug}', [CatalogController::class, 'brand'])->name('brands.show');
Route::get('/produit/{product:slug}', [ProductController::class, 'show'])->name('products.show');

Route::get('/page/{page:slug}', PageController::class)->name('pages.show');
Route::get('/faq', FaqController::class)->name('faq');
