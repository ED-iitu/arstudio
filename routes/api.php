<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::controller(\App\Http\Controllers\API\V1\LoginController::class)->group(function(){
    Route::get('login/{provider}', 'redirectToProvider');
    Route::get('login/{provider}/callback', 'handleProviderCallback');
    Route::post('login', 'login');
    Route::post('register', 'register');
});

Route::middleware('auth:sanctum')->controller(\App\Http\Controllers\API\V1\LoginController::class)->group(function(){
    Route::get('getUser', 'getUser');
    Route::post('user/update', 'updateProfile');
});

Route::middleware('auth:sanctum')->controller(\App\Http\Controllers\API\V1\ArController::class)->group(function (){
    Route::post('ar', 'create');
    Route::post('ar/update', 'update');
    Route::get('gallery', 'getGallery');
});

Route::controller(\App\Http\Controllers\API\V1\ArController::class)->group(function (){
    Route::get('ar/live', 'getArByGroupId');
    Route::get('ar', 'getByGroupId');
    Route::get('ar-info-images', 'getInfoImages');
});

Route::controller(\App\Http\Controllers\API\V1\TariffController::class)->group(function (){
    Route::get('tariffs', 'getAll');
});

Route::controller(\App\Http\Controllers\API\V1\QuestionController::class)->group(function (){
    Route::get('questions', 'getAll');
});

Route::controller(\App\Http\Controllers\API\V1\PurchaseController::class)->group(function (){
    Route::get('purchase', 'purchase');
});

Route::controller(\App\Http\Controllers\API\V1\UserQuestionController::class)->group(function (){
    Route::post('question', 'create');
});
