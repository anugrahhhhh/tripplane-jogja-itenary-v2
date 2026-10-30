<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\SecureAccountAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NotMeController extends Controller
{
    /**
     * Halaman konfirmasi; GET tidak mengubah data apa pun.
     */
    public function show(Request $request, string $user): View|Response
    {
        // Signature dicek sebelum lookup agar id tak dikenal tidak bisa dibedakan (anti-enumerasi)
        $user = $request->hasValidSignature() ? User::find($user) : null;

        if (! $user) {
            return $this->expired();
        }

        return view('auth.not-me.confirm', ['user' => $user]);
    }

    public function confirm(Request $request, string $user, SecureAccountAction $secure): Response|RedirectResponse
    {
        $user = $request->hasValidSignature() ? User::find($user) : null;

        if (! $user) {
            return $this->expired();
        }

        $secure->handle($user);

        // Password lama tidak boleh tetap berlaku sebelum pemilik selesai reset
        $user->forceFill(['password' => Hash::make(Str::random(64))])->save();

        if (Auth::check() && Auth::id() === $user->id) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()
            ->route('password.reset', ['token' => Password::createToken($user), 'email' => $user->email])
            ->with('google_hint', $user->google_id !== null);
    }

    private function expired(): Response
    {
        return response()->view('auth.not-me.expired', [], 403);
    }
}
