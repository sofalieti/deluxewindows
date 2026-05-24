<?php

use App\Http\Controllers\WebflowMirrorController;
use App\Http\Controllers\WebflowPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WebflowMirrorController::class, 'home']);

Route::get('/webflow-preview', [WebflowPageController::class, 'home']);
Route::get('/webflow-preview/{webflowPath}', [WebflowPageController::class, 'show'])
    ->where('webflowPath', '.*');

Route::fallback([WebflowMirrorController::class, 'catchAll']);
