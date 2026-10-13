<?php

use App\Http\Controllers\CarController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::get('/cars/{slug}', [CarController::class, 'show'])->name('cars.show');

Route::view('/privacy', 'legal.privacy')->name('legal.privacy');
Route::view('/terms', 'legal.terms')->name('legal.terms');
Route::view('/accessibility', 'legal.accessibility')->name('legal.accessibility');

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
