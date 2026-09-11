<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Zone;
use App\Models\ZoneSpace;

class DashboardController extends Controller
{
    public function index()
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);

        return view('dashboard', [
            'stats' => [
                'users' => User::count(),
                'bookings' => Transaction::count(),
                'zones' => Zone::count(),
                'spaces' => ZoneSpace::count(),
                'pending' => Transaction::where('booking_status', 'pending')->count(),
            ],
            'recentBookings' => Transaction::query()->latest()->limit(8)->get(),
        ]);
    }
}
