<?php

use App\Http\Controllers\Api\MemberController;
use Illuminate\Support\Facades\Route;

Route::get('/members', [MemberController::class, 'index'])->name('api.members.index');
