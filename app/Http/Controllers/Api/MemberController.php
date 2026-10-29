<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemberProfile;
use Illuminate\Http\JsonResponse;

class MemberController extends Controller
{
    /**
     * Public list of member names for the standalone face-registration app.
     */
    public function index(): JsonResponse
    {
        $members = MemberProfile::query()
            ->with('user:id,name')
            ->whereHas('user')
            ->orderBy('id')
            ->get()
            ->map(fn (MemberProfile $profile): array => [
                'name' => $profile->user->name,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'count' => $members->count(),
            'data' => $members,
        ]);
    }
}
