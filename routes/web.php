<?php

use App\Http\Controllers\CarController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::get('/cars/{slug}', [CarController::class, 'show'])->name('cars.show');
