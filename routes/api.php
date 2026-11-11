<?php

use App\Http\Controllers\Api\DeviceAccessController;
use App\Http\Controllers\Api\MemberController;
use Illuminate\Support\Facades\Route;

Route::get('/members', [MemberController::class, 'index'])->name('api.members.index');
Route::post('/members/{member}/face-photo', [MemberController::class, 'storeFacePhoto'])->middleware('throttle:10,1')->name('api.members.face-photo');
Route::post('/device/access', DeviceAccessController::class)
    ->middleware('throttle:60,1')
    ->name('api.device.access');