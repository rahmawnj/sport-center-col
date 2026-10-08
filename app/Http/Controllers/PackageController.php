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
        $data = $request->validate($this->rules());
        $rules = $data['pricing_rules'];
        unset($data['pricing_rules']);
        $data['duration_minutes'] = $data['duration_minutes'] ?? 0;

        $package = $facility->packages()->create($data);
        $package->pricingRules()->createMany($rules);

        return back()->with('success', 'Paket berhasil dibuat.');
    }

    public function update(Request $request, Package $package): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $rules = $data['pricing_rules'];
        unset($data['pricing_rules']);
        $data['duration_minutes'] = $data['duration_minutes'] ?? 0;

        $package->update($data);
        $package->pricingRules()->delete();
        $package->pricingRules()->createMany($rules);

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
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['session', 'membership', 'entry'])],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'required_if:type,session'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'required_if:type,membership'],
            'pricing_rules' => ['required', 'array', 'min:1'],
            'pricing_rules.*.day_type' => ['required', Rule::in(['weekday', 'weekend', 'all'])],
            'pricing_rules.*.date_start' => ['nullable', 'date'],
            'pricing_rules.*.date_end' => ['nullable', 'date', 'after_or_equal:pricing_rules.*.date_start'],
            'pricing_rules.*.start_time' => ['nullable', 'date_format:H:i', 'required_with:pricing_rules.*.end_time'],
            'pricing_rules.*.end_time' => ['nullable', 'date_format:H:i', 'after:pricing_rules.*.start_time'],
            'pricing_rules.*.price' => ['required', 'numeric', 'min:0'],
            'pricing_rules.*.priority' => ['nullable', 'integer'],
        ];
    }
}
