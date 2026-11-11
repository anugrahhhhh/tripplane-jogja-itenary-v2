<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Destination;
use App\Models\Itinerary;
use App\Models\Review;
use App\Models\User;
use App\Services\SystemHealth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(SystemHealth $health): View
    {
        return view('admin.dashboard', [
            'systemHealth' => $health->report(),
            'totalDestinations' => Destination::count(),
            'totalCategories' => Category::count(),
            'totalTravelers' => User::where('role', 'traveler')->count(),
            'totalItineraries' => Itinerary::count(),
            'pendingReviews' => Review::where('is_visible', false)->count(),
            'totalReviews' => Review::count(),
            'latestReviews' => Review::with(['user', 'destination'])->latest()->limit(5)->get(),
            'destinationsPerCategory' => Category::withCount('destinations')->orderBy('name')->get(),
            'topFavoritedDestinations' => $this->topFavoritedDestinations(),
            'categoryPopularity' => $this->categoryPopularityByFavorites(),
            'monthlyItineraryTrend' => $this->monthlyItineraryTrend(),
        ]);
    }

    /**
     * 8 destinasi dengan jumlah favorit terbanyak, untuk grafik "paling
     * difavoritkan". Destinasi tanpa favorit sama sekali tidak disertakan
     * supaya grafik tidak dipenuhi batang kosong.
     */
    protected function topFavoritedDestinations()
    {
        return Destination::withCount('favorites')
            ->having('favorites_count', '>', 0)
            ->orderByDesc('favorites_count')
            ->limit(8)
            ->get();
    }

    /**
     * Popularitas tiap kategori diukur dari total favorit yang dikumpulkan
     * seluruh destinasi di kategori itu (bukan sekadar jumlah destinasinya).
     */
    protected function categoryPopularityByFavorites()
    {
        return Category::query()
            ->select('categories.*')
            ->selectSub(function ($query) {
                $query->selectRaw('count(*)')
                    ->from('favorites')
                    ->join('destinations', 'destinations.id', '=', 'favorites.destination_id')
                    ->whereColumn('destinations.category_id', 'categories.id');
            }, 'favorites_count')
            ->orderByDesc('favorites_count')
            ->get();
    }

    /**
     * Jumlah itinerary yang dibuat per bulan, 6 bulan terakhir (termasuk
     * bulan berjalan). Bulan tanpa itinerary tetap muncul dengan nilai 0
     * supaya grafik trennya tidak berlubang.
     *
     * @return array<int, array{label: string, total: int}>
     */
    protected function monthlyItineraryTrend(): array
    {
        $since = now()->subMonths(5)->startOfMonth();

        $counts = Itinerary::query()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as total")
            ->where('created_at', '>=', $since)
            ->groupBy('month')
            ->pluck('total', 'month');

        $trend = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $key = $month->format('Y-m');

            $trend[] = [
                'label' => $month->translatedFormat('M Y'),
                'total' => (int) ($counts[$key] ?? 0),
            ];
        }

        return $trend;
    }
}
