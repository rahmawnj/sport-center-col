<?php

use App\Http\Controllers\CourtController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberDashboardController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\PublicHomeController;
use App\Http\Controllers\PublicMembershipController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\VerifyController;
use App\Http\Middleware\EnsureUserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicHomeController::class, 'index'])->name('home');
Route::get('membership', [PublicMembershipController::class, 'index'])->name('membership.index');

Route::get('book', [PublicBookingController::class, 'index'])->name('booking.index');
Route::get('book/availability', [PublicBookingController::class, 'availability'])->name('booking.availability');
Route::post('book', [PublicBookingController::class, 'store'])->name('booking.store');
Route::get('booking/receipt/{booking}', [PublicBookingController::class, 'receipt'])->name('booking.receipt');

Route::get('verify', [VerifyController::class, 'show'])->name('verify');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function (Request $request) {
        if ($request->user()->role?->name === 'Member') {
            return redirect()->route('member.dashboard');
        }

        // abort_unless($request->user()->role?->name === 'Superadmin', 403);

        return app(DashboardController::class)->index($request);
    })->name('dashboard');

    Route::get('member/dashboard', [MemberDashboardController::class, 'index'])
        ->middleware(EnsureUserRole::class.':Member')
        ->name('member.dashboard');

    Route::prefix('member')->name('member.')->middleware(EnsureUserRole::class.':Member')->group(function () {
        Route::get('bookings', [MemberDashboardController::class, 'bookings'])->name('bookings');
        Route::get('membership', [MemberDashboardController::class, 'membership'])->name('membership');
        Route::get('payments', [MemberDashboardController::class, 'payments'])->name('payments');
        Route::get('profile', [MemberDashboardController::class, 'profile'])->name('profile');
        Route::redirect('settings', '/member/profile')->name('settings');
    });

    Route::middleware([EnsureUserRole::class.':Superadmin'])->group(function () {

        Route::get('facilities', [FacilityController::class, 'index'])->name('facilities.index');
        Route::post('facilities', [FacilityController::class, 'store'])->name('facilities.store');
        Route::get('facilities/{facility}', [FacilityController::class, 'show'])->name('facilities.show');
        Route::put('facilities/{facility}', [FacilityController::class, 'update'])->name('facilities.update');
        Route::delete('facilities/{facility}', [FacilityController::class, 'destroy'])->name('facilities.destroy');

        Route::post('facilities/{facility}/packages', [PackageController::class, 'store'])->name('packages.store');
        Route::put('packages/{package}', [PackageController::class, 'update'])->name('packages.update');
        Route::delete('packages/{package}', [PackageController::class, 'destroy'])->name('packages.destroy');

        Route::post('facilities/{facility}/courts', [CourtController::class, 'store'])->name('courts.store');
        Route::put('courts/{court}', [CourtController::class, 'update'])->name('courts.update');
        Route::delete('courts/{court}', [CourtController::class, 'destroy'])->name('courts.destroy');

        Route::post('facilities/{facility}/devices', [DeviceController::class, 'store'])->name('devices.store');
        Route::put('devices/{device}', [DeviceController::class, 'update'])->name('devices.update');
        Route::delete('devices/{device}', [DeviceController::class, 'destroy'])->name('devices.destroy');

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

        Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::get('transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
        Route::post('transactions', [TransactionController::class, 'store'])->name('transactions.store');
        Route::get('transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
    });
});

require __DIR__.'/settings.php';
