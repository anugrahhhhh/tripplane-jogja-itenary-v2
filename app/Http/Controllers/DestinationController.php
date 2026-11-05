<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Destination;
use App\Services\DistanceService;
use App\Support\CatalogCache;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class DestinationController extends Controller
{
    private const REVIEWS_PER_PAGE = 10;

    /**
     * Katalog utama. Tanpa pencarian/filter: tampilkan destinasi
     * dikelompokkan per kategori (mode "jelajahi"). Begitu ada pencarian
     * atau filter kategori, tampilkan hasil sebagai grid datar (mode "hasil").
     */
    public function index(Request $request): View
    {
        $isSearching = $request->filled('q') || $request->filled('kategori') || $request->filled('urutkan');

        if (! $isSearching) {
            $categories = CatalogCache::remember(CatalogCache::BROWSE, function () {
                $categories = Category::withCount('destinations')->orderBy('name')->get();

                // Diisi manual per kategori (bukan eager-load dengan limit) karena
                // MySQL tidak bisa memakai alias withAvg() di dalam window function
                // yang dibuat Laravel untuk eager-load ber-limit per relasi.
                foreach ($categories as $category) {
                    $category->setRelation('destinations', Destination::where('category_id', $category->id)
                        ->where('is_active', true)
                        ->with('category')
                        ->withCount(['visibleReviews as reviews_count'])
                        ->withAvg('visibleReviews as reviews_avg_rating', 'rating')
                        ->orderByDesc('reviews_avg_rating')
                        ->limit(4)
                        ->get());
                }

                return $categories;
            });

            return view('destinations.index', [
                'mode' => 'browse',
                'categoryGroups' => $categories,
                'allCategories' => $categories,
            ]);
        }

        $result = $this->searchResults($request);

        return view('destinations.index', [
            'mode' => 'search',
            'destinations' => $result['destinations'],
            'categories' => $result['categories'],
            'allCategories' => $result['categories'],
            'sort' => $result['sort'],
        ]);
    }

    /**
     * Daftar destinasi per kategori: grid penuh dengan pagination.
     */
    public function byCategory(Request $request, Category $category): View
    {
        $result = $this->searchResults($request, $category);

        return view('destinations.category', [
            'destinations' => $result['destinations'],
            'activeCategory' => $category,
            'sort' => $result['sort'],
        ]);
    }

    public function show(Destination $destination): View
    {
        abort_unless($destination->is_active, 404);

        $destination->load(['category', 'photos']);

        // Ulasan dipaginasi (10 per halaman, parameter ?ulasan=) supaya
        // halaman tetap ringan walau ulasannya ratusan.
        $reviews = $destination->visibleReviews()
            ->with('user')
            ->latest()
            ->paginate(self::REVIEWS_PER_PAGE, ['*'], 'ulasan')
            ->withQueryString()
            ->fragment('ulasan');

        $userItineraries = auth()->check()
            ? auth()->user()->itineraries()->orderByDesc('start_date')->get()
            : collect();

        $related = Destination::where('is_active', true)
            ->where('category_id', $destination->category_id)
            ->where('id', '!=', $destination->id)
            ->withCount(['visibleReviews as reviews_count'])
            ->withAvg('visibleReviews as reviews_avg_rating', 'rating')
            ->inRandomOrder()
            ->limit(4)
            ->get();

        return view('destinations.show', [
            'destination' => $destination,
            'reviews' => $reviews,
            'isFavorited' => $destination->isFavoritedBy(auth()->user()),
            'userItineraries' => $userItineraries,
            'related' => $related,
        ]);
    }

    protected function searchResults(Request $request, ?Category $category = null): array
    {
        $query = Destination::query()->where('is_active', true)->with('category');

        if ($category) {
            $query->where('category_id', $category->id);
        } elseif ($request->filled('kategori')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->query('kategori')));
        }

        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->query('q').'%');
        }

        $query->withCount(['visibleReviews as reviews_count'])
            ->withAvg('visibleReviews as reviews_avg_rating', 'rating');

        $sort = $request->query('urutkan', 'rating');
        $hasLocation = $request->filled('lat') && $request->filled('lng');
        $perPage = 12;

        if ($sort === 'jarak' && $hasLocation) {
            $lat = (float) $request->query('lat');
            $lng = (float) $request->query('lng');

            $all = $query->get()->map(function (Destination $destination) use ($lat, $lng) {
                $destination->distance_km = round(
                    DistanceService::haversineKm($lat, $lng, $destination->latitude, $destination->longitude),
                    1
                );

                return $destination;
            })->sortBy('distance_km')->values();

            $page = LengthAwarePaginator::resolveCurrentPage();
            $destinations = new LengthAwarePaginator(
                $all->forPage($page, $perPage)->values(),
                $all->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            match ($sort) {
                'terbaru' => $query->orderByDesc('created_at'),
                default => $query->orderByDesc('reviews_avg_rating'),
            };

            $destinations = $query->paginate($perPage)->withQueryString();
        }

        return [
            'destinations' => $destinations,
            'categories' => CatalogCache::remember(
                CatalogCache::CATEGORIES,
                fn () => Category::withCount('destinations')->orderBy('name')->get()
            ),
            'sort' => $sort,
        ];
    }
}
