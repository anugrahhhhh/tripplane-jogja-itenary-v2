<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\TwoFactorGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, TwoFactorGate $gate): RedirectResponse
    {
        $user = $request->validatedUser();

        // Halaman masuk ini khusus wisatawan. Akun admin punya pintu masuk
        // terpisah di /admin/login agar akses kedua peran benar-benar
        // terpisah, bukan sekadar diarahkan ulang setelah berhasil masuk.
        if ($user->isAdmin()) {
            throw ValidationException::withMessages([
                'email' => 'Akun ini adalah akun Admin. Silakan masuk lewat halaman khusus Admin.',
            ]);
        }

        return $gate->pass($request, $user, 'password', $request->session()->pull('url.intended', route('home')));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
