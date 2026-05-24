<?php

use App\Http\Controllers\WebflowPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/webflow-preview', [WebflowPageController::class, 'home']);
Route::get('/webflow-preview/{webflowPath}', [WebflowPageController::class, 'show'])
    ->where('webflowPath', '.*');
