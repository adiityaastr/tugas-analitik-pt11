<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClassificationController;

Route::get('/', [ClassificationController::class, 'index']);
Route::post('/predict', [ClassificationController::class, 'predict']);
