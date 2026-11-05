<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Support\FavoriteLookup;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function index(): View
    {
        // Destinasi yang dinonaktifkan admin disembunyikan (kartunya akan
        // mengarah ke 404); muncul lagi otomatis kalau diaktifkan kembali.
        $favorites = auth()->user()->favorites()
            ->whereHas('destination', fn ($query) => $query->where('is_active', true))
            ->with('destination.category')
            ->latest()
            ->paginate(12);

        return view('favorites.index', ['favorites' => $favorites]);
    }

    public function store(Destination $destination): RedirectResponse
    {
        abort_unless($destination->is_active, 404);

        auth()->user()->favorites()->firstOrCreate(['destination_id' => $destination->id]);

        return back()->with('status', 'Ditambahkan ke favorit.');
    }

    public function destroy(Destination $destination): RedirectResponse
    {
        auth()->user()->favorites()->where('destination_id', $destination->id)->delete();
        // Hapus lewat query builder tidak memicu event model, jadi manual.
        FavoriteLookup::forget(auth()->user());

        return back()->with('status', 'Dihapus dari favorit.');
    }
}
