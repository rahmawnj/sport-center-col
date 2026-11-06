<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\Package;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class MembershipPackageController extends Controller
{
    public function index(): Response
    {
        $packages = Package::query()
            ->where('type', 'membership')
            ->whereHas('facility', fn ($query) => $query->where('has_membership', true)->where('status', 'active'))
            ->with('facility:id,name')
            ->latest()
            ->paginate(10)
            ->through(fn (Package $package) => [
                'id' => $package->id,
                'name' => $package->name,
                'facility_id' => $package->facility_id,
                'facility_name' => $package->facility?->name,
                'duration_days' => $package->duration_days,
                'price' => (float) $package->price,
                'member_count' => $package->userMemberships()->count(),
            ])
            ->withQueryString();

        return Inertia::render('membership-packages/Index', [
            'packages' => $packages,
            'facilities' => Facility::query()
                ->where('status', 'active')
                ->where('has_membership', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'stats' => [
                'packages' => Package::where('type', 'membership')->whereHas('facility', fn ($query) => $query->where('has_membership', true)->where('status', 'active'))->count(),
                'members' => DB::table('user_memberships')
                    ->join('packages', 'packages.id', '=', 'user_memberships.package_id')
                    ->where('packages.type', 'membership')
                    ->distinct('user_memberships.user_id')
                    ->count('user_memberships.user_id'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['type'] = 'membership';
        $data['duration_minutes'] = 0;

        try {
            Package::create($data);
        } catch (Throwable $exception) {
            Log::error('Gagal membuat paket membership.', [
                'package_name' => $data['name'] ?? null,
                'facility_id' => $data['facility_id'] ?? null,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        return back()->with('success', 'Paket membership berhasil ditambahkan.');
    }

    public function update(Request $request, Package $package): RedirectResponse
    {
        abort_unless($package->type === 'membership', 404);

        $data = $this->validatedData($request);

        if ($package->userMemberships()->exists() && (int) $data['facility_id'] !== (int) $package->facility_id) {
            return back()->with('error', 'Olahraga tidak dapat diubah karena paket ini sudah dimiliki member.');
        }

        $data['type'] = 'membership';
        $data['duration_minutes'] = 0;

        try {
            $package->update($data);
        } catch (Throwable $exception) {
            Log::error('Gagal memperbarui paket membership.', [
                'package_id' => $package->id,
                'package_name' => $data['name'] ?? null,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        return back()->with('success', 'Paket membership berhasil diperbarui.');
    }

    public function destroy(Package $package): RedirectResponse
    {
        abort_unless($package->type === 'membership', 404);

        if ($package->userMemberships()->exists()) {
            return back()->with('error', 'Paket sudah dimiliki member dan tidak dapat dihapus. Edit paketnya atau pertahankan untuk riwayat member.');
        }

        $package->delete();

        return back()->with('success', 'Paket membership berhasil dihapus.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'facility_id' => ['required', 'integer', Rule::exists('facilities', 'id')->where(fn ($query) => $query->where('has_membership', true)->where('status', 'active'))],
            'duration_days' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);
    }
}
