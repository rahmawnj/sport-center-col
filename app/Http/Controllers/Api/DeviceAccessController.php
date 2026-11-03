<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\MemberProfile;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeviceAccessController extends Controller
{
    /**
     * Unified endpoint for member-code access, ticket QR access and face-device callbacks.
     *
     * Face device mapping:
     * - info.IdCard is the member_code.
     * - info.DeviceID is matched against devices.device_code.
     * - SanpPic is intentionally ignored (never logged or persisted here).
     */
    public function __invoke(Request $request): JsonResponse
    {
        $isFaceCallback = $request->input('operator') === 'VerifyPush';

        if ($isFaceCallback) {
            $info = $request->input('info');

            if (! is_array($info)) {
                throw ValidationException::withMessages([
                    'info' => ['Payload VerifyPush wajib memiliki object info.'],
                ]);
            }

            $deviceIdentifier = $info['DeviceID'] ?? null;
            $identifier = isset($info['IdCard']) ? trim((string) $info['IdCard']) : '';
            $similarity = is_numeric($info['Similarity1'] ?? null)
                ? (float) $info['Similarity1']
                : null;
            $verified = (string) ($info['VerifyStatus'] ?? '') === '1';

            if ($deviceIdentifier === null || $identifier === '') {
                throw ValidationException::withMessages([
                    'info.IdCard' => ['info.IdCard dan info.DeviceID wajib diisi.'],
                ]);
            }

            $device = $this->findDevice((string) $deviceIdentifier);
            $minimumSimilarity = (float) config('services.face_device.minimum_similarity', 80);
            $source = 'face';
            $type = 'member';

            if (! $verified || $similarity === null || $similarity < $minimumSimilarity) {
                return $this->deny(
                    $device,
                    $type,
                    $identifier,
                    $source,
                    ! $verified ? 'Verifikasi wajah ditolak oleh perangkat.' : 'Similarity wajah di bawah batas minimum.',
                    $similarity
                );
            }
        } else {
            $validated = $request->validate([
                'device_id' => ['required', 'string', 'max:100'],
                'device_type' => ['required', 'in:member,qrcode'],
                'member_code' => ['required_if:device_type,member', 'nullable', 'string', 'max:100'],
                'scan_code' => ['required_if:device_type,qrcode', 'nullable', 'string', 'max:255'],
            ]);

            $device = $this->findDevice($validated['device_id']);
            $type = $validated['device_type'];
            $identifier = trim((string) ($type === 'member'
                ? $validated['member_code']
                : $validated['scan_code']));
            $source = 'api';
            $similarity = null;
        }

        if (! $device || $device->status !== 'active') {
            return $this->deny(
                $device,
                $type,
                $identifier,
                $source,
                'Perangkat tidak ditemukan atau tidak aktif.',
                $similarity
            );
        }

        if ($type === 'member') {
            $profile = MemberProfile::query()
                ->with('user')
                ->where('member_code', $identifier)
                ->first();

            if (! $profile || ! $profile->user || ! $this->hasActiveMembership($profile->user, $device)) {
                return $this->deny(
                    $device,
                    $type,
                    $identifier,
                    $source,
                    ! $profile ? 'Kode member tidak ditemukan.' : 'Membership tidak aktif atau bukan untuk fasilitas perangkat ini.',
                    $similarity,
                    $profile?->user
                );
            }

            return response()->json([
                'ok' => true,
                'allowed' => true,
                'message' => 'Akses diberikan.',
                'data' => [
                    'type' => 'member',
                    'device_id' => $device->id,
                    'member' => [
                        'id' => $profile->user->id,
                        'name' => $profile->user->name,
                        'member_code' => $profile->member_code,
                    ],
                    'similarity' => $similarity,
                ],
            ]);
        }

        $transaction = Transaction::query()
            ->with('user')
            ->where('booking_code', $identifier)
            ->first();

        $validTicket = $transaction
            && $transaction->payment_status === 'fully_paid'
            && $transaction->booking_status === 'approved'
            && DB::table('transaction_details')
                ->where('transaction_id', $transaction->id)
                ->where('start_time', '<=', now())
                ->where('end_time', '>=', now())
                ->exists()
            && (! $transaction->device_id || (int) $transaction->device_id === (int) $device->id);

        if (! $validTicket) {
            return $this->deny(
                $device,
                'qrcode',
                $identifier,
                $source,
                'QR tiket tidak valid, belum lunas/disetujui, di luar jadwal, atau bukan untuk perangkat ini.',
                null,
                $transaction?->user
            );
        }

        return response()->json([
            'ok' => true,
            'allowed' => true,
            'message' => 'Akses tiket diberikan.',
            'data' => [
                'type' => 'qrcode',
                'device_id' => $device->id,
                'booking_code' => $transaction->booking_code,
                'customer_name' => $transaction->user?->name ?? $transaction->guest_name,
                'transaction_id' => $transaction->id,
            ],
        ]);
    }

    private function findDevice(string $identifier): ?Device
    {
        return Device::query()
            ->where('device_code', $identifier)
            ->when(ctype_digit($identifier), fn ($query) => $query->orWhere('id', (int) $identifier))
            ->first();
    }

    private function hasActiveMembership(User $user, Device $device): bool
    {
        return $user->userMemberships()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->whereHas('package', fn ($query) => $query->where('facility_id', $device->facility_id))
            ->exists();
    }

    private function deny(
        ?Device $device,
        string $type,
        string $identifier,
        string $source,
        string $reason,
        ?float $similarity = null,
        ?User $user = null
    ): JsonResponse {

        // Business-level denials return HTTP 200 so simple gate controllers can read allowed=false.
        return response()->json([
            'ok' => true,
            'allowed' => false,
            'message' => $reason,
            'data' => [
                'type' => $type,
                'device_id' => $device?->id,
            ],
        ]);
    }


}
