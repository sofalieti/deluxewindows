<?php

use App\Http\Controllers\ClassicSiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ClassicSiteController::class, 'home']);
Route::get('/windows', [ClassicSiteController::class, 'windowsIndex']);
Route::get('/doors', [ClassicSiteController::class, 'doorsIndex']);

Route::permanentRedirect('/windows/martin-elevate', '/brand-collections/brand-marvin-elevate-collection');
Route::permanentRedirect('/windows/martin-vivid', '/brand-collections/brand-marvin-vivid-collection');
Route::permanentRedirect('/windows/marvin-essne', '/brand-collections/brand-marvin-essential-collection');
Route::permanentRedirect('/windows/marvin-modern', '/brand-collections/brand-marvin-modern-collection');
Route::permanentRedirect('/windows/marvin-ultimate', '/brand-collections/brand-marvin-ultimate-collection');
Route::permanentRedirect('/windows/marvin-windows', '/brands/marvin');

Route::get('/windows/{slug}', [ClassicSiteController::class, 'windowBySlug'])
    ->where('slug', '[A-Za-z0-9\-]+');

Route::get('/doors/{slug}', [ClassicSiteController::class, 'doorBySlug'])
    ->where('slug', '[A-Za-z0-9\-]+');

Route::permanentRedirect('/brand', '/brands');

Route::get('/brands', [ClassicSiteController::class, 'brandIndex']);

Route::get('/brands/{slug}', [ClassicSiteController::class, 'brandBySlug'])
    ->where('slug', '[A-Za-z0-9\-]+');

Route::get('/door-brands/{slug}', [ClassicSiteController::class, 'doorBrandBySlug'])
    ->where('slug', '[A-Za-z0-9\-]+');

Route::get('/brand-collections/{slug}', [ClassicSiteController::class, 'brandCollectionBySlug'])
    ->where('slug', '[A-Za-z0-9\-]+');

Route::get('/collection/{slug}', [ClassicSiteController::class, 'legacyBrandCollectionRedirect'])
    ->where('slug', '[A-Za-z0-9\-]+');

Route::get('/window-type/{slug}', [ClassicSiteController::class, 'windowTypeBySlug'])
    ->where('slug', '[A-Za-z0-9\-]+');

Route::get('/door-types/{slug}', [ClassicSiteController::class, 'doorTypeBySlug'])
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
Route::get('/privacy-policy', [ClassicSiteController::class, 'privacyPolicy']);
Route::get('/terms', [ClassicSiteController::class, 'terms']);
Route::get('/special-offers', [ClassicSiteController::class, 'specialOffers']);
Route::post('/contact-form', [ClassicSiteController::class, 'submitContactForm'])->name('contact.submit');

