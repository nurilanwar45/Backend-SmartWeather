<?php

use App\Http\Controllers\WeatherController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'SmartWeather Backend',
        
    ]);
});
Route::get('/weather/current', [WeatherController::class, 'current']);