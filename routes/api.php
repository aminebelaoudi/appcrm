<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OpenHouseController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/properties/{listingKey}/open-house/defaults', [OpenHouseController::class, 'defaults'])
    ->name('open-houses.defaults');
Route::post('/properties/{listingKey}/open-houses', [OpenHouseController::class, 'store'])
    ->name('open-houses.store');
Route::get('/open-houses/{openHouseId}/registrations', [OpenHouseController::class, 'registrations'])
    ->name('open-houses.registrations');
