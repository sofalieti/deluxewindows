<?php

use App\Http\Controllers\ClassicSiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ClassicSiteController::class, 'home']);
Route::get('/alternative', [ClassicSiteController::class, 'alternative']);
Route::get('/windows/{slug}', [ClassicSiteController::class, 'windowBySlug'])
    ->where('slug', '[A-Za-z0-9\-]+');

