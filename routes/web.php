<?php

use App\Http\Controllers\ClassicSiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ClassicSiteController::class, 'home']);
Route::get('/windows/{slug}', [ClassicSiteController::class, 'windowBySlug'])
    ->where('slug', '[A-Za-z0-9\-]+');

Route::get('/brands/{slug}', [ClassicSiteController::class, 'brandBySlug'])
    ->where('slug', '[A-Za-z0-9\-]+');

Route::get('/brand-collections/{slug}', [ClassicSiteController::class, 'brandCollectionBySlug'])
    ->where('slug', '[A-Za-z0-9\-]+');

