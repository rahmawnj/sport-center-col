<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\Facility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourtController extends Controller
{
    public function store(Request $request, Facility $facility): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $data['amenities'] = $this->normalizeAmenities($request->input('amenities'));
        $data['price_adjustment'] = $data['price_adjustment'] ?? 0;

        $facility->courts()->create($data);

        return back()->with('success', 'Lapangan berhasil dibuat.');
    }

    public function update(Request $request, Court $court): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $data['amenities'] = $this->normalizeAmenities($request->input('amenities'));
        $data['price_adjustment'] = $data['price_adjustment'] ?? 0;

        $court->update($data);

        return back()->with('success', 'Lapangan berhasil diperbarui.');
    }

    public function destroy(Court $court): RedirectResponse
    {
        abort_if(
            $court->bookings()->exists(),
            422,
            'Lapangan tidak dapat dihapus karena sudah memiliki booking.',
        );

        $court->delete();

        return back()->with('success', 'Lapangan berhasil dihapus.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price_adjustment' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['available', 'maintenance', 'inactive'])],
        ];
    }

    /**
     * @return array<int, string>|null
     */
    private function normalizeAmenities(mixed $value): ?array
    {
        $items = is_array($value) ? $value : explode(',', (string) $value);
        $items = array_values(array_unique(array_filter(
            array_map(fn ($item) => trim((string) $item), $items),
            fn ($item) => $item !== '',
        )));

        return $items ?: null;
    }
}
