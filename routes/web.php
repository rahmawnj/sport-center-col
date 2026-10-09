<?php

use App\Http\Controllers\CourtController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\SportController;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\VerifyController;
use Illuminate\Support\Facades\Route;


Route::get('/sitemap.xml', [PublicPageController::class, 'sitemap'])->name('sitemap');
Route::get('/', [PublicPageController::class, 'home'])->name('home');
Route::get('/zones/{zone}', [PublicPageController::class, 'showZone'])->name('zones.show');

Route::get('book', [PublicBookingController::class, 'index'])->name('booking.index');
Route::get('book/availability', [PublicBookingController::class, 'availability'])->name('booking.availability');\nRoute::get('book/add-ons-stock', [PublicBookingController::class, 'addOnsStock'])->name('booking.add-ons-stock');
Route::post('book', [PublicBookingController::class, 'store'])->name('booking.store');

Route::get('verify', [VerifyController::class, 'show'])->name('verify');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('facilities', [FacilityController::class, 'index'])->name('facilities.index');
    Route::post('facilities', [FacilityController::class, 'store'])->name('facilities.store');
    Route::get('facilities/{facility}', [FacilityController::class, 'show'])->name('facilities.show');
    Route::put('facilities/{facility}', [FacilityController::class, 'update'])->name('facilities.update');
    Route::delete('facilities/{facility}', [FacilityController::class, 'destroy'])->name('facilities.destroy');

    Route::get('sports', [SportController::class, 'index'])->name('sports.index');
    Route::get('sports/{sport}', [SportController::class, 'show'])->name('sports.show');
    Route::post('sports', [SportController::class, 'store'])->name('sports.store');
    Route::put('sports/{sport}', [SportController::class, 'update'])->name('sports.update');
    Route::delete('sports/{sport}', [SportController::class, 'destroy'])->name('sports.destroy');

    Route::post('facilities/{facility}/packages', [PackageController::class, 'store'])->name('packages.store');
    Route::put('packages/{package}', [PackageController::class, 'update'])->name('packages.update');
    Route::delete('packages/{package}', [PackageController::class, 'destroy'])->name('packages.destroy');

    Route::post('facilities/{facility}/courts', [CourtController::class, 'store'])->name('courts.store');
    Route::put('courts/{court}', [CourtController::class, 'update'])->name('courts.update');
    Route::delete('courts/{court}', [CourtController::class, 'destroy'])->name('courts.destroy');

    Route::get('members', [MemberController::class, 'index'])->name('members.index');
    Route::post('members', [MemberController::class, 'store'])->name('members.store');
    Route::put('members/{member}', [MemberController::class, 'update'])->name('members.update');
    Route::delete('members/{member}', [MemberController::class, 'destroy'])->name('members.destroy');

    Route::get('memberships', [MembershipController::class, 'index'])->name('memberships.index');
    Route::get('memberships/{membership}', [MembershipController::class, 'show'])->name('memberships.show');
    Route::put('memberships/{membership}', [MembershipController::class, 'update'])->name('memberships.update');
    Route::post('memberships/{membership}/renew', [MembershipController::class, 'renew'])->name('memberships.renew');

    Route::get('reports/{type?}', [ReportController::class, 'index'])->name('reports.index');

    Route::get('trainers', [TrainerController::class, 'index'])->name('trainers.index');
    Route::get('trainers/{trainer}', [TrainerController::class, 'show'])->name('trainers.show');
    Route::post('trainers', [TrainerController::class, 'store'])->name('trainers.store');
    Route::put('trainers/{trainer}', [TrainerController::class, 'update'])->name('trainers.update');
    Route::delete('trainers/{trainer}', [TrainerController::class, 'destroy'])->name('trainers.destroy');

    Route::get('resources', [ResourceController::class, 'index'])->name('resources.index');
    Route::get('sports/resources/create', [ResourceController::class, 'create'])->name('resources.create');
    Route::get('sports/resources/{resource}/edit', [ResourceController::class, 'edit'])->name('resources.edit');
    Route::post('resources', [ResourceController::class, 'store'])->name('resources.store');
    Route::put('resources/{resource}', [ResourceController::class, 'update'])->name('resources.update');
    Route::delete('resources/{resource}', [ResourceController::class, 'destroy'])->name('resources.destroy');

    Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
    Route::post('transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::get('transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
});

require __DIR__.'/settings.php';
