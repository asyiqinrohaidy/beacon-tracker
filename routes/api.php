<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BeaconController;
use App\Http\Controllers\Api\PresenceController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\FingerprintController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Beacon
    Route::post('/beacon/data', [BeaconController::class, 'receive']);

    // Presence
    Route::get('/presence/current', [PresenceController::class, 'current']);
    Route::get('/presence/logs', [PresenceController::class, 'logs']);

    // Locations
    Route::get('/locations', [LocationController::class, 'index']);
    Route::post('/locations', [LocationController::class, 'store']);

    // Employees
    Route::get('/employees', [EmployeeController::class, 'index']);
    Route::post('/employees', [EmployeeController::class, 'store']);

    // Fingerprinting
    Route::post('/fingerprint/train', [FingerprintController::class, 'train']);
    Route::get('/fingerprint', [FingerprintController::class, 'index']);
    Route::post('/fingerprint/predict', [FingerprintController::class, 'predict']);
    Route::delete('/fingerprint/reset', [FingerprintController::class, 'reset']);
});