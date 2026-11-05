<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\Auth\TrustedDeviceManager;
use App\Services\Auth\TwoFactorAuthenticator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SecureAccountAction
{
    public function __construct(
        private TrustedDeviceManager $trustedDevices,
        private TwoFactorAuthenticator $twoFactor,
    ) {}

    /**
     * Kunci akun: putus semua sesi, cabut perangkat tepercaya, reset 2FA, dan invalidasi remember token.
     */
    public function handle(User $user): void
    {
        DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();

        $this->trustedDevices->revokeAll($user);
        $this->twoFactor->disable($user);

        $user->forceFill(['remember_token' => Str::random(60)])->save();
    }
}
