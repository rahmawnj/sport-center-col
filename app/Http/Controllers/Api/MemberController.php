<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemberProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
                'id' => $profile->id,
                'name' => $profile->user->name,
                'face_photo_path' => $profile->face_photo_path,
                'face_photo_url' => $profile->face_photo_path ? Storage::disk('public')->url($profile->face_photo_path) : null,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'count' => $members->count(),
            'data' => $members,
        ]);
    }

    /** Store or replace a member face photo from the standalone registration app. */
    public function storeFacePhoto(Request $request, MemberProfile $member): JsonResponse
    {
        $validated = $request->validate(['photo' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:10240']]);
        $newPath = $validated['photo']->storePublicly('members/faces', 'public');
        if (! $newPath) return response()->json(['success' => false, 'message' => 'Foto gagal disimpan ke server.'], 500);
        $oldPath = $member->face_photo_path;
        $member->forceFill(['face_photo_path' => $newPath])->save();
        if ($oldPath && $oldPath !== $newPath) Storage::disk('public')->delete($oldPath);
        return response()->json(['success' => true, 'message' => $oldPath ? 'Foto wajah berhasil diperbarui.' : 'Foto wajah berhasil disimpan.', 'face_photo_path' => $newPath, 'face_photo_url' => Storage::disk('public')->url($newPath), 'replaced' => (bool) $oldPath], 201);
    }
}
