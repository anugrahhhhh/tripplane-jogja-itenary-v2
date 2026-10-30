<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Destination;
use App\Models\DestinationPhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DestinationController extends Controller
{
    public function index(Request $request): View
    {
        $destinations = Destination::query()
            ->with('category')
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->query('q').'%'))
            ->when($request->filled('kategori'), fn ($q) => $q->where('category_id', $request->query('kategori')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.destinations.index', [
            'destinations' => $destinations,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.destinations.create', [
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        if ($request->hasFile('cover_photo')) {
            $data['cover_photo'] = $request->file('cover_photo')->store('destinations', 'public');
        }

        $destination = Destination::create($data);

        $this->storeGalleryPhotos($request, $destination);

        return redirect()->route('admin.destinations.index')->with('status', 'Destinasi berhasil ditambahkan.');
    }

    public function edit(Destination $destination): View
    {
        $destination->load('photos');

        return view('admin.destinations.edit', [
            'destination' => $destination,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Destination $destination): RedirectResponse
    {
        $data = $this->validateData($request, $destination);

        if ($request->hasFile('cover_photo')) {
            if ($destination->cover_photo) {
                Storage::disk('public')->delete($destination->cover_photo);
            }

            $data['cover_photo'] = $request->file('cover_photo')->store('destinations', 'public');
        }

        $destination->update($data);

        $this->storeGalleryPhotos($request, $destination);

        return redirect()->route('admin.destinations.index')->with('status', 'Destinasi berhasil diperbarui.');
    }

    public function destroy(Destination $destination): RedirectResponse
    {
        if ($destination->cover_photo) {
            Storage::disk('public')->delete($destination->cover_photo);
        }

        foreach ($destination->photos as $photo) {
            Storage::disk('public')->delete($photo->path);
        }

        $destination->delete();

        return redirect()->route('admin.destinations.index')->with('status', 'Destinasi berhasil dihapus.');
    }

    public function destroyPhoto(Destination $destination, DestinationPhoto $photo): RedirectResponse
    {
        abort_unless($photo->destination_id === $destination->id, 404);

        Storage::disk('public')->delete($photo->path);
        $photo->delete();

        return back()->with('status', 'Foto galeri berhasil dihapus.');
    }

    protected function storeGalleryPhotos(Request $request, Destination $destination): void
    {
        if (! $request->hasFile('photos')) {
            return;
        }

        foreach ($request->file('photos') as $file) {
            $destination->photos()->create([
                'path' => $file->store('destinations', 'public'),
            ]);
        }
    }

    protected function validateData(Request $request, ?Destination $destination = null): array
    {
        $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'recommendation_reason' => ['nullable', 'string'],
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'price_min' => ['required', 'integer', 'min:0'],
            'price_max' => ['required', 'integer', 'gte:price_min'],
            'open_time' => ['required', 'date_format:H:i'],
            'close_time' => ['required', 'date_format:H:i'],
            'closed_days' => ['nullable', 'array'],
            'closed_days.*' => ['integer', 'between:1,7', 'distinct'],
            'cover_photo' => ['nullable', 'image', 'max:2048'],
            'photos.*' => ['nullable', 'image', 'max:2048'],
        ]);

        return [
            'category_id' => $request->integer('category_id'),
            'name' => $request->input('name'),
            'slug' => $this->resolveSlug($request->input('name'), $destination),
            'description' => $request->input('description'),
            'recommendation_reason' => $request->input('recommendation_reason'),
            'address' => $request->input('address'),
            'latitude' => $request->float('latitude'),
            'longitude' => $request->float('longitude'),
            'price_min' => $request->integer('price_min'),
            'price_max' => $request->integer('price_max'),
            'open_time' => $request->input('open_time'),
            'close_time' => $request->input('close_time'),
            'closed_days' => collect($request->input('closed_days', []))->map(fn ($d) => (int) $d)->unique()->sort()->values()->all() ?: null,
            'is_active' => $request->boolean('is_active'),
        ];
    }

    /**
     * Slug hanya dihitung ulang kalau ini destinasi baru atau namanya
     * benar-benar berubah — supaya URL destinasi yang sudah ada tetap
     * stabil, dan supaya menyimpan ulang tanpa mengubah nama tidak pernah
     * memicu bentrok slug (ini akar masalah error 500 yang pernah terjadi
     * saat admin menyimpan ulang "Jalan Malioboro" tanpa mengganti nama).
     * Kalau nama baru menghasilkan slug yang bentrok dengan destinasi lain
     * (mis. dua tempat sengaja diberi nama sama seperti "Jalan Malioboro"
     * di dua kategori), tambahkan angka di belakang alih-alih membiarkan
     * constraint database gagal mentah-mentah.
     */
    protected function resolveSlug(string $name, ?Destination $destination): string
    {
        if ($destination && $destination->name === $name) {
            return $destination->slug;
        }

        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while (
            Destination::where('slug', $slug)
                ->when($destination, fn ($query) => $query->whereKeyNot($destination->id))
                ->exists()
        ) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
