<?php

namespace App\Http\Controllers;

use App\Models\Trainer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrainerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));

        $items = Trainer::query()
            ->when($search !== '', fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('specialty', 'like', "%{$search}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('trainers/Index', [
            'trainers' => $items,
            'filters' => ['search' => $search],
            'stats' => ['total' => Trainer::count()],
        ]);
    }

    public function show(Trainer $trainer): Response
    {
        return Inertia::render('trainers/Show', ['trainer' => $trainer]);
    }

    public function store(Request $request): RedirectResponse
    {
        Trainer::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
        ]));

        return back()->with('success', 'Trainer berhasil dibuat.');
    }

    public function update(Request $request, Trainer $trainer): RedirectResponse
    {
        $trainer->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
        ]));

        return back()->with('success', 'Trainer berhasil diperbarui.');
    }

    public function destroy(Trainer $trainer): RedirectResponse
    {
        $trainer->delete();

        return back()->with('success', 'Trainer berhasil dihapus.');
    }
}
