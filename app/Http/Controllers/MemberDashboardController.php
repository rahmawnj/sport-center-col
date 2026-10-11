<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class MemberDashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('member/Dashboard');
    }
}
