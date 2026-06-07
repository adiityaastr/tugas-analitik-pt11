<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClassificationController;

Route::get('/', [ClassificationController::class, 'index']);
Route::post('/predict', [ClassificationController::class, 'predict']);
Route::get('/accuracy/{model}', [ClassificationController::class, 'accuracy']);
Route::post('/retrain', [ClassificationController::class, 'retrain']);
