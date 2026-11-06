<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\Package;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PackageController extends Controller
{
    public function store(Request $request, Facility $facility): RedirectResponse
    {
        $data = $request->validate($this->rules($facility->id));
        $rules = $data['pricing_rules'];
        $addons = $data['trainer_addons'] ?? [];
        unset($data['pricing_rules'], $data['trainer_addons']);
        $data['duration_minutes'] = $data['duration_minutes'] ?? 0;

        $package = $facility->packages()->create($data);
        $package->pricingRules()->createMany($rules);
        $package->trainerAddons()->sync($this->addonSyncPayload($addons));

        return back()->with('success', 'Paket berhasil dibuat.');
    }

    public function update(Request $request, Package $package): RedirectResponse
    {
        $data = $request->validate($this->rules($package->facility_id));
        $rules = $data['pricing_rules'];
        $addons = $data['trainer_addons'] ?? [];
        unset($data['pricing_rules'], $data['trainer_addons']);
        $data['duration_minutes'] = $data['duration_minutes'] ?? 0;

        $package->update($data);
        $package->pricingRules()->delete();
        $package->pricingRules()->createMany($rules);
        $package->trainerAddons()->sync($this->addonSyncPayload($addons));

        return back()->with('success', 'Paket berhasil diperbarui.');
    }

    public function destroy(Package $package): RedirectResponse
    {
        abort_if(
            $package->userMemberships()->exists(),
            422,
            'Paket tidak dapat dihapus karena sudah dimiliki member.',
        );

        $package->delete();

        return back()->with('success', 'Paket berhasil dihapus.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(int $facilityId): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['session', 'entry'])],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'required_if:type,session'],
            'duration_days' => ['prohibited'],
            'pricing_rules' => ['required', 'array', 'min:1'],
            'trainer_addons' => ['nullable', 'array'],
            'trainer_addons.*.trainer_id' => ['required', 'integer', 'distinct', Rule::exists('facility_trainer', 'trainer_id')->where('facility_id', $facilityId)],
            'trainer_addons.*.price' => ['required', 'numeric', 'min:0'],
            'pricing_rules.*.day_type' => ['required', Rule::in(['weekday', 'weekend', 'all'])],
            'pricing_rules.*.date_start' => ['nullable', 'date'],
            'pricing_rules.*.date_end' => ['nullable', 'date', 'after_or_equal:pricing_rules.*.date_start'],
            'pricing_rules.*.start_time' => ['nullable', 'date_format:H:i', 'required_with:pricing_rules.*.end_time'],
            'pricing_rules.*.end_time' => ['nullable', 'date_format:H:i', 'after:pricing_rules.*.start_time'],
            'pricing_rules.*.price' => ['required', 'numeric', 'min:0'],
            'pricing_rules.*.priority' => ['nullable', 'integer'],
        ];
    }

    /**
     * @param  array<int, array{trainer_id: int|string, price: int|float|string}>  $addons
     * @return array<int, array{price: float}>
     */
    private function addonSyncPayload(array $addons): array
    {
        $sync = [];

        foreach ($addons as $addon) {
            $sync[(int) $addon['trainer_id']] = ['price' => (float) $addon['price']];
        }

        return $sync;
    }
}
