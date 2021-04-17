<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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


Route::post('initiateStk',[\App\Http\Controllers\MpesaController::class,'initStk']);
Route::post(config('mpesa.endpoints.stk_callback'),[\App\Http\Controllers\PaymentsReceiver::class,'receive']);
Route::post(config('mpesa.endpoints.b2c_result'),[\App\Http\Controllers\PaymentsReceiver::class,'b2cResult']);
Route::post(config('mpesa.endpoints.b2c_queue_timeout'),[\App\Http\Controllers\PaymentsReceiver::class,'b2cTimeout']);
Route::post(config('mpesa.endpoints.b2c_endpoint'),[\App\Http\Controllers\PaymentsReceiver::class,'initiateB2C']);