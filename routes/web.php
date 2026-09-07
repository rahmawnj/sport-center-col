<?php

use App\Http\Controllers\AddOnController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\MembershipPackageController;
use App\Http\Controllers\PricingRateController;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ZoneController;
use App\Http\Controllers\ZoneSpaceController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::get('users', [UserController::class, 'index'])->name('users.index'); Route::post('users', [UserController::class, 'store'])->name('users.store'); Route::get('users/{user}', [UserController::class, 'show'])->name('users.show'); Route::put('users/{user}', [UserController::class, 'update'])->name('users.update'); Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('zones', [ZoneController::class, 'index'])->name('zones.index'); Route::post('zones', [ZoneController::class, 'store'])->name('zones.store'); Route::get('zones/{zone}', [ZoneController::class, 'show'])->name('zones.show'); Route::put('zones/{zone}', [ZoneController::class, 'update'])->name('zones.update'); Route::delete('zones/{zone}', [ZoneController::class, 'destroy'])->name('zones.destroy');
    Route::get('zone-spaces', [ZoneSpaceController::class, 'index'])->name('zone-spaces.index'); Route::post('zone-spaces', [ZoneSpaceController::class, 'store'])->name('zone-spaces.store'); Route::put('zone-spaces/{zoneSpace}', [ZoneSpaceController::class, 'update'])->name('zone-spaces.update'); Route::delete('zone-spaces/{zoneSpace}', [ZoneSpaceController::class, 'destroy'])->name('zone-spaces.destroy');
    Route::get('pricing-rates', [PricingRateController::class, 'index'])->name('pricing-rates.index'); Route::post('pricing-rates', [PricingRateController::class, 'store'])->name('pricing-rates.store'); Route::put('pricing-rates/{pricingRate}', [PricingRateController::class, 'update'])->name('pricing-rates.update'); Route::delete('pricing-rates/{pricingRate}', [PricingRateController::class, 'destroy'])->name('pricing-rates.destroy');
    Route::get('membership-packages', [MembershipPackageController::class, 'index'])->name('membership-packages.index'); Route::get('membership-packages/{membershipPackage}', [MembershipPackageController::class, 'show'])->name('membership-packages.show'); Route::post('membership-packages', [MembershipPackageController::class, 'store'])->name('membership-packages.store'); Route::put('membership-packages/{membershipPackage}', [MembershipPackageController::class, 'update'])->name('membership-packages.update'); Route::delete('membership-packages/{membershipPackage}', [MembershipPackageController::class, 'destroy'])->name('membership-packages.destroy');
    Route::get('trainers', [TrainerController::class, 'index'])->name('trainers.index'); Route::get('trainers/{trainer}', [TrainerController::class, 'show'])->name('trainers.show'); Route::post('trainers', [TrainerController::class, 'store'])->name('trainers.store'); Route::put('trainers/{trainer}', [TrainerController::class, 'update'])->name('trainers.update'); Route::delete('trainers/{trainer}', [TrainerController::class, 'destroy'])->name('trainers.destroy');
    Route::get('facilities', [FacilityController::class, 'index'])->name('facilities.index'); Route::get('facilities/{facility}', [FacilityController::class, 'show'])->name('facilities.show'); Route::post('facilities', [FacilityController::class, 'store'])->name('facilities.store'); Route::put('facilities/{facility}', [FacilityController::class, 'update'])->name('facilities.update'); Route::delete('facilities/{facility}', [FacilityController::class, 'destroy'])->name('facilities.destroy');
    Route::get('add-ons', [AddOnController::class, 'index'])->name('add-ons.index'); Route::get('add-ons/{addOn}', [AddOnController::class, 'show'])->name('add-ons.show'); Route::post('add-ons', [AddOnController::class, 'store'])->name('add-ons.store'); Route::put('add-ons/{addOn}', [AddOnController::class, 'update'])->name('add-ons.update'); Route::delete('add-ons/{addOn}', [AddOnController::class, 'destroy'])->name('add-ons.destroy');
});

require __DIR__.'/settings.php';
