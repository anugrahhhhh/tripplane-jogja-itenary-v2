<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public const GENERIC_STATUS = 'Jika email tersebut terdaftar, link untuk mengatur ulang kata sandi sudah kami kirim. '
        .'Cek kotak masuk dan folder spam. Belum menerima? Tunggu 1 menit sebelum meminta lagi.';

    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::sendResetLink($request->only('email'));

        // Jawaban SAMA untuk semua kasus (terkirim, email tidak terdaftar,
        // atau diminta ulang terlalu cepat). Kalau berbeda, siapa pun bisa
        // mengecek email mana yang punya akun JogjaTrip (user enumeration).
        return back()->with('status', self::GENERIC_STATUS);
    }
}
