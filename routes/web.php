<?php

use App\Http\Controllers\WebflowPageController;
use App\Http\Controllers\WebflowSiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WebflowSiteController::class, 'home']);

Route::get('/webflow-preview', [WebflowPageController::class, 'home']);
Route::get('/webflow-preview/{webflowPath}', [WebflowPageController::class, 'show'])
    ->where('webflowPath', '.*');

Route::fallback([WebflowSiteController::class, 'catchAll']);
