<?php

use App\Http\Controllers\Api\DeviceAccessController;
use App\Http\Controllers\Api\MemberController;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\Route;

Route::get('/members', [MemberController::class, 'index'])->name('api.members.index');
Route::post('/members/{member}/face-photo', [MemberController::class, 'storeFacePhoto'])->middleware('throttle:10,1')->name('api.members.face-photo');
Route::post('/device/access', DeviceAccessController::class)
    ->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsWithRedis::class])
    ->name('api.device.access');
