<?php

use App\Http\Controllers\BookingTimeSlotController;
use App\Http\Controllers\CourtController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberDashboardController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\MembershipPackageController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\PublicHomeController;
use App\Http\Controllers\PublicMembershipController;
use App\Http\Controllers\PublicMembershipRegistrationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\TrainerDashboardController;
use App\Http\Controllers\TrainerPackageController;
use App\Http\Controllers\TrainerSessionController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\VerifyController;
use App\Http\Middleware\EnsureUserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicHomeController::class, 'index'])->name('home');
Route::get('pelatih/{trainer}', [PublicHomeController::class, 'showTrainer'])->name('public.trainers.show');
Route::get('membership/register', [PublicMembershipRegistrationController::class, 'create'])->name('membership.register');
Route::post('membership/register', [PublicMembershipRegistrationController::class, 'store'])->name('membership.register.store');
Route::post('membership/register/switch-account', function (Request $request) {
    Illuminate\Support\Facades\Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->name('membership.register.switch-account');
Route::get('membership/{package}', [PublicMembershipController::class, 'show'])->whereNumber('package')->name('membership.show');

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

        if ($request->user()->role?->name === 'Trainer') {
            return redirect()->route('trainer.dashboard');
        }

        abort_unless($request->user()->role?->name === 'Superadmin', 403);

        return app(DashboardController::class)->index($request);
    })->name('dashboard');

    Route::get('member/trainer-schedules/{schedule}', [TrainerSessionController::class, 'showMember'])->middleware(EnsureUserRole::class.':Member')->name('member.trainer-schedules.show');
    Route::post('member/trainer-schedules/{schedule}/confirm', [TrainerSessionController::class, 'confirmMember'])->middleware(EnsureUserRole::class.':Member')->name('member.trainer-schedules.confirm');

    Route::get('member/classes', [MemberClassController::class, 'index'])->name('member.classes');
    Route::post('member/classes/{schedule}/register', [MemberClassController::class, 'store'])->name('member.classes.register');

    Route::get('member/dashboard', [MemberDashboardController::class, 'index'])
        ->middleware(EnsureUserRole::class.':Member')
        ->name('member.dashboard');

    Route::prefix('member')->name('member.')->middleware(EnsureUserRole::class.':Member')->group(function () {
        Route::get('bookings', [MemberDashboardController::class, 'bookings'])->name('bookings');
        Route::get('membership', [MemberDashboardController::class, 'membership'])->name('membership');
        Route::get('membership/{membership}', [MemberDashboardController::class, 'membershipShow'])->name('membership.show');
        Route::get('payments', [MemberDashboardController::class, 'payments'])->name('payments');
        Route::get('payments/{transaction}', [MemberDashboardController::class, 'paymentShow'])->name('payments.show');
        Route::post('payments/{transaction}/cancel', [MemberDashboardController::class, 'cancelPayment'])->name('payments.cancel');
        Route::get('profile', [MemberDashboardController::class, 'profile'])->name('profile');
        Route::redirect('settings', '/member/profile')->name('settings');
    });

    Route::prefix('trainer')->name('trainer.')->middleware(EnsureUserRole::class.':Trainer')->group(function () {
        Route::get('schedules/{schedule}', [TrainerSessionController::class, 'showTrainer'])->name('schedules.show');
        Route::post('schedules/{schedule}/confirm', [TrainerSessionController::class, 'confirmTrainer'])->name('schedules.confirm');
        Route::get('dashboard', [TrainerDashboardController::class, 'index'])->name('dashboard');
        Route::get('members', [TrainerDashboardController::class, 'members'])->name('members');
        Route::get('members/{memberTrainerPackage}', [TrainerDashboardController::class, 'memberShow'])->name('members.show');
        Route::get('profile', [TrainerDashboardController::class, 'profile'])->name('profile');
    });

    Route::middleware([EnsureUserRole::class.':Superadmin'])->group(function () {
        Route::get('classes', [ClassManagementController::class, 'index'])->name('classes.index');
        Route::post('classes', [ClassManagementController::class, 'store'])->name('classes.store');
        Route::put('classes/{sportClass}', [ClassManagementController::class, 'update'])->name('classes.update');
        Route::delete('classes/{sportClass}', [ClassManagementController::class, 'destroy'])->name('classes.destroy');
        Route::post('classes/{sportClass}/schedules', [ClassManagementController::class, 'storeSchedule'])->name('classes.schedules.store');
        Route::put('class-schedules/{schedule}', [ClassManagementController::class, 'updateSchedule'])->name('class-schedules.update');
        Route::delete('class-schedules/{schedule}', [ClassManagementController::class, 'destroySchedule'])->name('class-schedules.destroy');
        Route::put('class-schedules/{schedule}/status', [ClassManagementController::class, 'updateScheduleStatus'])->name('class-schedules.status');

        Route::post('facilities/{facility}/booking-time-slots', [BookingTimeSlotController::class, 'store'])->name('facilities.booking-time-slots.store');
        Route::put('facilities/{facility}/booking-time-slots/{bookingTimeSlot}', [BookingTimeSlotController::class, 'update'])->name('facilities.booking-time-slots.update');
        Route::delete('facilities/{facility}/booking-time-slots/{bookingTimeSlot}', [BookingTimeSlotController::class, 'destroy'])->name('facilities.booking-time-slots.destroy');

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
        Route::get('members/{member}', [MemberController::class, 'show'])->name('members.show');
        Route::post('members', [MemberController::class, 'store'])->name('members.store');
        Route::put('members/{member}', [MemberController::class, 'update'])->name('members.update');
        Route::delete('members/{member}', [MemberController::class, 'destroy'])->name('members.destroy');

        Route::get('membership-packages', [MembershipPackageController::class, 'index'])->name('membership-packages.index');
        Route::post('membership-packages', [MembershipPackageController::class, 'store'])->name('membership-packages.store');
        Route::put('membership-packages/{package}', [MembershipPackageController::class, 'update'])->name('membership-packages.update');
        Route::delete('membership-packages/{package}', [MembershipPackageController::class, 'destroy'])->name('membership-packages.destroy');

        Route::get('memberships', [MembershipController::class, 'index'])->name('memberships.index');
        Route::get('memberships/{membership}', [MembershipController::class, 'show'])->name('memberships.show');
        Route::put('memberships/{membership}', [MembershipController::class, 'update'])->name('memberships.update');
        Route::post('memberships/{membership}/renew', [MembershipController::class, 'renew'])->name('memberships.renew');

        Route::redirect('reports/membership', '/reports/trainer', 301);
        Route::get('reports/{type?}', [ReportController::class, 'index'])->name('reports.index');

        Route::get('trainers', [TrainerController::class, 'index'])->name('trainers.index');
        Route::get('trainers/{trainer}', [TrainerController::class, 'show'])->name('trainers.show');
        Route::post('trainers', [TrainerController::class, 'store'])->name('trainers.store');
        Route::put('trainers/{trainer}', [TrainerController::class, 'update'])->name('trainers.update');
        Route::delete('trainers/{trainer}', [TrainerController::class, 'destroy'])->name('trainers.destroy');

        Route::get('trainer-packages', [TrainerPackageController::class, 'index'])->name('trainer-packages.index');
        Route::get('membership-trainers', [TrainerPackageController::class, 'membershipTrainers'])->name('membership-trainers.index');
        Route::get('membership-trainers/{memberTrainerPackage}', [TrainerPackageController::class, 'membershipTrainerShow'])->name('membership-trainers.show');
        Route::post('membership-trainers/{memberTrainerPackage}/schedules', [TrainerSessionController::class, 'store'])->name('membership-trainers.schedules.store');
        Route::put('trainer-session-schedules/{schedule}', [TrainerSessionController::class, 'update'])->name('trainer-session-schedules.update');
        Route::post('trainer-session-schedules/{schedule}/result', [TrainerSessionController::class, 'setResult'])->name('trainer-session-schedules.result');
        Route::post('trainer-packages', [TrainerPackageController::class, 'store'])->name('trainer-packages.store');
        Route::put('trainer-packages/{trainerPackage}', [TrainerPackageController::class, 'update'])->name('trainer-packages.update');
        Route::delete('trainer-packages/{trainerPackage}', [TrainerPackageController::class, 'destroy'])->name('trainer-packages.destroy');
        Route::post('trainer-packages/purchase', [TrainerPackageController::class, 'purchase'])->name('trainer-packages.purchase');
        Route::post('trainer-packages/{memberTrainerPackage}/use-session', [TrainerPackageController::class, 'useSession'])->name('trainer-packages.use-session');

        Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::get('transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
        Route::post('transactions', [TransactionController::class, 'store'])->name('transactions.store');
        Route::get('transactions/{transaction}/edit', [TransactionController::class, 'edit'])->name('transactions.edit');
        Route::put('transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
        Route::get('transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
    });
});

require __DIR__.'/settings.php';
