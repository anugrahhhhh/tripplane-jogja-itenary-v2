<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Destination;
use App\Support\CatalogCache;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        [$categories, $popular] = CatalogCache::remember(CatalogCache::HOME, fn () => [
            Category::withCount('destinations')->orderBy('name')->get(),
            Destination::query()
                ->where('is_active', true)
                ->with('category')
                ->withCount(['visibleReviews as reviews_count'])
                ->withAvg('visibleReviews as reviews_avg_rating', 'rating')
                ->orderByDesc('reviews_avg_rating')
                ->limit(8)
                ->get(),
        ]);

        return view('home', [
            'categories' => $categories,
            'popular' => $popular,
        ]);
    }
}
