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
        $facility->courts()->create($request->validate($this->rules()));

        return back()->with('success', 'Lapangan berhasil dibuat.');
    }

    public function update(Request $request, Court $court): RedirectResponse
    {
        $court->update($request->validate($this->rules()));

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
            'status' => ['required', Rule::in(['available', 'maintenance', 'inactive'])],
        ];
    }
}
