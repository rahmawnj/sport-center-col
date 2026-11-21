<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $memberCode = null;
        $authUser = null;

        if ($user) {
            $user->loadMissing('role');
            $authUser = $user->toArray();
            $authUser['role'] = $user->role?->only('name');
            $authUser['image_profile_url'] = $user->image_profile
                ? '/storage/'.ltrim($user->image_profile, '/')
                : null;
            unset($authUser['image_profile']);
        }

        if ($user && $user->role?->name === 'Member') {
            $memberCode = $user->loadMissing('memberProfile')->memberProfile?->member_code;
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'memberCode' => $memberCode,
            'auth' => [
                'user' => $authUser,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
