<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Auth\SecureAccountAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\TwoFactorResetByAdminNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->withCount(['favorites', 'reviews', 'itineraries'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = $request->query('q');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users]);
    }

    public function show(User $user): View
    {
        $user->load(['favorites.destination', 'reviews.destination', 'itineraries']);

        return view('admin.users.show', ['user' => $user]);
    }

    public function resetTwoFactor(Request $request, User $user, SecureAccountAction $secureAccount): RedirectResponse
    {
        // Admin tidak boleh mereset 2FA-nya sendiri.
        abort_if($request->user()->is($user), 403);

        $secureAccount->handle($user);
        $user->notify(new TwoFactorResetByAdminNotification);

        return back()->with('status', '2FA pengguna berhasil di-reset.');
    }
}
