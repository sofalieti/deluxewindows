<?php

use App\Http\Controllers\Admin\WebflowImageUploadController;
use App\Http\Controllers\ClassicSiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ClassicSiteController::class, 'home']);
Route::get('/windows/{slug}', [ClassicSiteController::class, 'windowBySlug'])
    ->where('slug', '[A-Za-z0-9\-]+');

Route::post('/admin/webflow/upload-image', [WebflowImageUploadController::class, 'upload'])
    ->middleware(['web', 'platform']);
