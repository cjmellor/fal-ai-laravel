<?php

use Illuminate\Support\Facades\Route;

// Add routes here to test your FalAi package
Route::get('/', function () {
    return response()->json([
        'message' => 'FalAi Laravel package is working!',
        'config' => config('fal-ai'),
        'facade_available' => class_exists('\Cjmellor\FalAi\Facades\FalAi'),
    ]);
});
