<?php

namespace Tests\Feature\Auth\TwoFactor;

use App\Models\User;
use App\Services\Auth\TwoFactorAuthenticator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorAuthenticatorTest extends TestCase
{
    use RefreshDatabase;

    private function service(): TwoFactorAuthenticator
    {
        return app(TwoFactorAuthenticator::class);
    }

    public function test_generated_secret_is_base32_of_at_least_16_chars(): void
    {
        $this->assertMatchesRegularExpression('/^[A-Z2-7]{16,}$/', $this->service()->generateSecret());
    }

    public function test_valid_current_code_returns_timestep(): void
    {
        $google2fa = new Google2FA;
        $secret = $this->service()->generateSecret();

        $this->assertIsInt($this->service()->verifySecretCode($secret, $google2fa->getCurrentOtp($secret), null));
    }

    public function test_code_from_previous_slot_is_accepted(): void
    {
        $google2fa = new Google2FA;
        $secret = $this->service()->generateSecret();
        $code = $google2fa->oathTotp($secret, $google2fa->getTimestamp() - 1);

        $this->assertNotNull($this->service()->verifySecretCode($secret, $code, null));
    }

    public function test_code_two_slots_old_is_rejected(): void
    {
        $google2fa = new Google2FA;
        $secret = $this->service()->generateSecret();
        $code = $google2fa->oathTotp($secret, $google2fa->getTimestamp() - 3);

        $this->assertNull($this->service()->verifySecretCode($secret, $code, null));
    }

    public function test_code_with_spaces_is_accepted(): void
    {
        $google2fa = new Google2FA;
        $secret = $this->service()->generateSecret();
        $otp = $google2fa->getCurrentOtp($secret);
        $spaced = substr($otp, 0, 3).' '.substr($otp, 3);

        $this->assertNotNull($this->service()->verifySecretCode($secret, $spaced, null));
    }

    public function test_same_code_cannot_be_used_twice_for_user(): void
    {
        $google2fa = new Google2FA;
        $user = User::factory()->create();
        $code = $google2fa->getCurrentOtp($user->two_factor_secret);

        $this->assertTrue($this->service()->verifyUserCode($user, $code));
        $this->assertFalse($this->service()->verifyUserCode($user->fresh(), $code));
    }

    public function test_recovery_codes_are_eight_in_expected_format(): void
    {
        $codes = $this->service()->generateRecoveryCodes();

        $this->assertCount(8, $codes['plain']);
        $this->assertCount(8, $codes['hashed']);
        foreach ($codes['plain'] as $i => $plain) {
            $this->assertMatchesRegularExpression('/^[a-z0-9]{5}-[a-z0-9]{5}$/', $plain);
            $this->assertNotSame($plain, $codes['hashed'][$i]);
        }
    }

    public function test_recovery_code_is_single_use(): void
    {
        $codes = $this->service()->generateRecoveryCodes();
        $user = User::factory()->withRecoveryCodes($codes['plain'])->create();

        $this->assertTrue($this->service()->useRecoveryCode($user, $codes['plain'][0]));
        $this->assertFalse($this->service()->useRecoveryCode($user->fresh(), $codes['plain'][0]));
        $this->assertSame(7, $user->fresh()->recoveryCodesRemaining());
    }

    public function test_recovery_code_is_case_and_whitespace_insensitive(): void
    {
        $codes = $this->service()->generateRecoveryCodes();
        $user = User::factory()->withRecoveryCodes($codes['plain'])->create();

        $this->assertTrue($this->service()->useRecoveryCode($user, ' '.strtoupper($codes['plain'][0]).' '));
    }

    public function test_two_factor_columns_are_hidden_and_secret_encrypted(): void
    {
        $user = User::factory()->create();

        $array = $user->toArray();
        foreach (array_keys($array) as $key) {
            $this->assertStringStartsNotWith('two_factor_', $key);
        }

        $raw = DB::table('users')->where('id', $user->id)->value('two_factor_secret');
        $this->assertNotSame($user->two_factor_secret, $raw);
    }

    public function test_factory_default_has_two_factor_and_state_can_remove_it(): void
    {
        $with = User::factory()->create();
        $this->assertTrue($with->hasTwoFactorEnabled());
        $this->assertNotNull($with->two_factor_secret);

        $without = User::factory()->withoutTwoFactor()->create();
        $this->assertFalse($without->hasTwoFactorEnabled());
        $this->assertNull($without->two_factor_secret);
        $this->assertNull($without->two_factor_recovery_codes);
        $this->assertNull($without->two_factor_last_used_step);
    }
}
