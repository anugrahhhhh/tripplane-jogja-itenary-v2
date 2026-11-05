<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Satu user hanya punya satu ulasan per destinasi (lihat unique
     * constraint di migration reviews). Submit ulang akan meng-update
     * ulasan yang sudah ada, bukan membuat data baru.
     */
    public function store(Request $request, Destination $destination): RedirectResponse
    {
        abort_unless($destination->is_active, 404);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        // is_visible sengaja tidak disentuh di sini: kolom itu default true
        // saat ulasan baru dibuat, dan tidak boleh otomatis "un-hide" ulasan
        // yang sudah disembunyikan admin hanya karena user mengedit isinya.
        $destination->reviews()->updateOrCreate(
            ['user_id' => auth()->id()],
            $data
        );

        return back()->with('status', 'Ulasan Anda berhasil disimpan.');
    }
}
