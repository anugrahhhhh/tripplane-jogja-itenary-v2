<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\SecureAccountAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class NotMeController extends Controller
{
    /**
     * Halaman konfirmasi; GET tidak mengubah data apa pun.
     */
    public function show(Request $request, User $user): View|Response
    {
        if (! $request->hasValidSignature()) {
            return $this->expired();
        }

        return view('auth.not-me.confirm', ['user' => $user]);
    }

    public function confirm(Request $request, User $user, SecureAccountAction $secure): Response|RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            return $this->expired();
        }

        $secure->handle($user);

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
