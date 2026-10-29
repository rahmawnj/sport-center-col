<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemberProfile;
use Illuminate\Http\JsonResponse;

class MemberController extends Controller
{
    /**
     * Public member data endpoint. No API key or authentication required.
     */
    public function index(): JsonResponse
    {
        $members = MemberProfile::query()
            ->with(['user.userMemberships'])
            ->whereHas('user')
            ->orderBy('id')
            ->get()
            ->map(function (MemberProfile $profile): array {
                $user = $profile->user;
                $hasActiveMembership = $user->userMemberships->contains(
                    fn ($membership): bool => $membership->isActive()
                );

                return [
                    'id' => $profile->id,
                    'user_id' => $user->id,
                    'member_code' => $profile->member_code,
                    'name' => $user->name,
                    'qr_code' => $profile->qr_code,
                    'membership_status' => $hasActiveMembership ? 'active' : 'inactive',
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'count' => $members->count(),
            'data' => $members,
        ]);
    }
}
