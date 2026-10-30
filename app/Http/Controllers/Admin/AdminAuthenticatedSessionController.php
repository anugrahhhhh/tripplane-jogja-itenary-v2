<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\TwoFactorGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminAuthenticatedSessionController extends Controller
{
    /**
     * Pintu masuk khusus Admin, terpisah dari halaman /login milik wisatawan.
     */
    public function create(): View
    {
        return view('auth.admin-login');
    }

    public function store(LoginRequest $request, TwoFactorGate $gate): RedirectResponse
    {
        $user = $request->validatedUser();

        // Belum ada yang login di titik ini, jadi cukup ditolak tanpa logout.
        if (! $user->isAdmin()) {
            throw ValidationException::withMessages([
                'email' => 'Akun ini bukan akun Admin. Silakan masuk lewat halaman khusus wisatawan.',
            ]);
        }

        return $gate->pass($request, $user, 'password', $request->session()->pull('url.intended', route('admin.dashboard')));
    }
}
