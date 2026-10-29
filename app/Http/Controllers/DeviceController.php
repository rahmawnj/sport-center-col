<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Facility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceController extends Controller
{
    public function store(Request $request, Facility $facility): RedirectResponse
    {
        $this->normalizeCourtId($request);

        $facility->devices()->create($request->validate($this->rules($facility)));

        return back()->with('success', 'Perangkat berhasil dibuat.');
    }

    public function update(Request $request, Device $device): RedirectResponse
    {
        $this->normalizeCourtId($request);

        $device->update($request->validate($this->rules($device->facility, $device)));

        return back()->with('success', 'Perangkat berhasil diperbarui.');
    }

    private function normalizeCourtId(Request $request): void
    {
        if ($request->input('court_id') === 'none') {
            $request->merge(['court_id' => null]);
        }
    }

    public function destroy(Device $device): RedirectResponse
    {
        $device->delete();

        return back()->with('success', 'Perangkat berhasil dihapus.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(Facility $facility, ?Device $device = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:100'],
            'court_id' => [
                'nullable',
                'integer',
                Rule::exists('courts', 'id')->where('facility_id', $facility->id),
            ],
            'device_code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('devices', 'device_code')->ignore($device?->id),
            ],
            'mqtt_topic' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('devices', 'mqtt_topic')->ignore($device?->id),
            ],
            'status' => ['required', Rule::in(['active', 'maintenance', 'inactive'])],
        ];
    }
}
