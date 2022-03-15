<?php

use App\Http\Controllers\InstagramAPIController;
use App\Http\Controllers\MainController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('donwload', [MainController::class,'download']);
Route::get('youtube-downloader',[MainController::class,'youtube'])->name('youtube');
Route::get('facebook-downloader',[MainController::class,'facebook'])->name('facebook');
Route::get('instagram-downloader',[MainController::class,'instagram'])->name('instagram');

// Route::get('youtube-downloader/{query}',[MainController::class,'search'])->name('search-query');
Route::post('search',[MainController::class,'search'])->name('search');
Route::post('facebookSearch',[MainController::class,'facebookSearch'])->name('facebookSearch');
Route::post('instagramSearch',[MainController::class,'instagramSearch'])->name('instagramSearch');

Route::get('cookie',[InstagramAPIController::class,'run'])->name('run');

