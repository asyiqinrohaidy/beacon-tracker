<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BeaconController;
use App\Http\Controllers\Api\PresenceController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\EmployeeController;

// Beacon data receiver (gateway will POST here)
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