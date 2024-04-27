<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
});

Route::prefix('paybox')->group(function () {
    Route::get('success', [\App\Http\Controllers\PayboxController::class, 'success']);
    Route::get('failure', [\App\Http\Controllers\PayboxController::class, 'failure']);
});
