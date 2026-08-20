<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PayoutController;

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

Route::get('/payouts', [PayoutController::class, 'index']);
Route::post('/payouts', [PayoutController::class, 'store']);
Route::patch('/payouts/{id}/status', [PayoutController::class, 'updateStatus']);
