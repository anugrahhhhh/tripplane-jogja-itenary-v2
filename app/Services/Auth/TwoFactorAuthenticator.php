<?php

namespace App\Services\Auth;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthenticator
{
    private const RECOVERY_CODE_COUNT = 8;

    public function __construct(private readonly Google2FA $google2fa) {}

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function qrCodeSvg(User $user, string $secret): string
    {
        $url = $this->google2fa->getQRCodeUrl(config('app.name'), $user->email, $secret);

        $writer = new Writer(new ImageRenderer(new RendererStyle(192), new SvgImageBackEnd));

        return $writer->writeString($url);
    }

    /**
     * @return int|null timestep yang cocok, null jika kode tidak valid / sudah dipakai
     */
    public function verifySecretCode(string $secret, string $code, ?int $lastUsedStep): ?int
    {
        $code = preg_replace('/\s+/', '', $code);

        $step = $this->google2fa->verifyKeyNewer($secret, $code, $lastUsedStep ?? 0, 1);

        return $step === false ? null : (int) $step;
    }

    public function verifyUserCode(User $user, string $code): bool
    {
        if ($user->two_factor_secret === null) {
            return false;
        }

        $step = $this->verifySecretCode($user->two_factor_secret, $code, $user->two_factor_last_used_step);

        if ($step === null) {
            return false;
        }

        $user->forceFill(['two_factor_last_used_step' => $step])->save();

        return true;
    }

    /**
     * @return array{plain: list<string>, hashed: list<string>}
     */
    public function generateRecoveryCodes(): array
    {
        $plain = [];
        for ($i = 0; $i < self::RECOVERY_CODE_COUNT; $i++) {
            $plain[] = Str::lower(Str::random(5).'-'.Str::random(5));
        }

        return [
            'plain' => $plain,
            'hashed' => array_map(fn (string $code) => Hash::make($code), $plain),
        ];
    }

    public function useRecoveryCode(User $user, string $code): bool
    {
        $code = strtolower(trim($code));
        $hashes = $user->two_factor_recovery_codes ?? [];

        foreach ($hashes as $index => $hash) {
            if (Hash::check($code, $hash)) {
                unset($hashes[$index]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($hashes)])->save();

                return true;
            }
        }

        return false;
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_used_step' => null,
        ])->save();
    }
}
