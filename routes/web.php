<?php

use App\Http\Controllers\ClassicSiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ClassicSiteController::class, 'home']);
Route::get('/windows', [ClassicSiteController::class, 'windowsIndex']);
Route::get('/doors', [ClassicSiteController::class, 'doorsIndex']);
Route::get('/windows/{slug}', [ClassicSiteController::class, 'windowBySlug'])
    ->where('slug', '[A-Za-z0-9\-]+');

Route::get('/doors/{slug}', [ClassicSiteController::class, 'doorBySlug'])
    ->where('slug', '[A-Za-z0-9\-]+');

Route::get('/brand', [ClassicSiteController::class, 'brandIndex']);

Route::get('/brands/{slug}', [ClassicSiteController::class, 'brandBySlug'])
    ->where('slug', '[A-Za-z0-9\-]+');

Route::get('/brand-collections/{slug}', [ClassicSiteController::class, 'brandCollectionBySlug'])
    ->where('slug', '[A-Za-z0-9\-]+');

Route::get('/window-type/{slug}', [ClassicSiteController::class, 'windowTypeBySlug'])
    ->where('slug', '[A-Za-z0-9\-]+');

Route::get('/blog', [ClassicSiteController::class, 'blogIndex']);

Route::get('/blog/{slug}', [ClassicSiteController::class, 'blogBySlug'])
    ->where('slug', '[A-Za-z0-9\-]+');

Route::get('/county-hub-pages/{slug}', [ClassicSiteController::class, 'countyHubBySlug'])
    ->where('slug', '[A-Za-z0-9\-]+');

Route::get('/window-replacement/{slug}', [ClassicSiteController::class, 'windowReplacementBySlug'])
    ->where('slug', '[A-Za-z0-9\-]+');

Route::get('/gallery', [ClassicSiteController::class, 'gallery']);
Route::get('/glossary', [ClassicSiteController::class, 'glossary']);
Route::get('/faq', [ClassicSiteController::class, 'faq']);
Route::get('/testimonials', [ClassicSiteController::class, 'testimonials']);
Route::get('/financing', [ClassicSiteController::class, 'financing']);
Route::get('/about', [ClassicSiteController::class, 'about']);
Route::get('/contacts', [ClassicSiteController::class, 'contacts']);
Route::get('/special-offers', [ClassicSiteController::class, 'specialOffers']);
Route::post('/contact-form', [ClassicSiteController::class, 'submitContactForm'])->name('contact.submit');

