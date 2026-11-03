<?php

use App\Http\Controllers\Api\MemberController;
use Illuminate\Support\Facades\Route;

Route::get('/members', [MemberController::class, 'index'])->name('api.members.index');\nRoute::post('/device/access', DeviceAccessController::class)->middleware('throttle:60,1')->name('api.device.access');
