<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\Trainer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TrainerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));

        $items = Trainer::query()
            ->with('facilities:id,name')
            ->when($search !== '', fn ($query) => $query
                ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('specialty', 'like', "%{$search}%")))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Trainer $trainer) => [
                'id' => $trainer->id,
                'name' => $trainer->name,
                'specialty' => $trainer->specialty,
                'phone' => $trainer->phone,
                'facility_ids' => $trainer->facilities->pluck('id')->map(fn ($id) => (int) $id)->all(),
                'facilities' => $trainer->facilities->pluck('name')->all(),
            ]);

        return Inertia::render('trainers/Index', [
            'trainers' => $items,
            'facilities' => Facility::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'filters' => ['search' => $search],
            'stats' => ['total' => Trainer::count()],
        ]);
    }

    public function show(Trainer $trainer): Response
    {
        $trainer->load('facilities:id,name');

        return Inertia::render('trainers/Show', ['trainer' => $trainer]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'facility_ids' => ['required', 'array', 'min:1'],
            'facility_ids.*' => ['integer', 'distinct', 'exists:facilities,id'],
        ]);

        DB::transaction(function () use ($data) {
            $trainer = Trainer::create([
                'name' => $data['name'],
                'specialty' => $data['specialty'] ?? null,
                'phone' => $data['phone'] ?? null,
            ]);
            $trainer->facilities()->sync($data['facility_ids']);
        });

        return back()->with('success', 'Trainer berhasil dibuat.');
    }

    public function update(Request $request, Trainer $trainer): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'facility_ids' => ['required', 'array', 'min:1'],
            'facility_ids.*' => ['integer', 'distinct', 'exists:facilities,id'],
        ]);

        DB::transaction(function () use ($data, $trainer) {
            $trainer->update([
                'name' => $data['name'],
                'specialty' => $data['specialty'] ?? null,
                'phone' => $data['phone'] ?? null,
            ]);
            $trainer->facilities()->sync($data['facility_ids']);
        });

        return back()->with('success', 'Trainer berhasil diperbarui.');
    }

    public function destroy(Trainer $trainer): RedirectResponse
    {
        if ($trainer->memberPackages()->exists()) {
            return back()->with('error', 'Trainer sudah memiliki riwayat paket member dan tidak dapat dihapus.');
        }

        $trainer->facilities()->detach();
        $trainer->delete();

        return back()->with('success', 'Trainer berhasil dihapus.');
    }
}
