<?php

namespace Tests\Feature\Profile;

use App\Models\TrustedDevice;
use App\Models\User;
use App\Services\Auth\TwoFactorAuthenticator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorSecurityTest extends TestCase
{
    use RefreshDatabase;

    private const CHROME_WINDOWS = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    private function userWithCodes(int $count = 8): User
    {
        return User::factory()
            ->withRecoveryCodes(array_map(fn ($i) => "aaaaa-bbbb{$i}", range(1, $count)))
            ->create();
    }

    public function test_profile_shows_two_factor_status_and_remaining_codes(): void
    {
        $user = $this->userWithCodes(5);

        $this->actingAs($user)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Keamanan')
            ->assertSee('Aktif')
            ->assertSee('5 dari 8');
    }

    public function test_regenerate_requires_valid_code(): void
    {
        $user = $this->userWithCodes(3);
        $before = $user->two_factor_recovery_codes;

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->post(route('profile.recovery-codes.regenerate'), ['code' => '000000'])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('code', null, 'twoFactor');

        $this->assertSame($before, $user->fresh()->two_factor_recovery_codes);
    }

    public function test_regenerate_replaces_old_codes(): void
    {
        $user = $this->userWithCodes(3);
        $code = (new Google2FA)->getCurrentOtp($user->two_factor_secret);

        $response = $this->actingAs($user)
            ->post(route('profile.recovery-codes.regenerate'), ['code' => $code]);

        $response->assertRedirect(route('two-factor.recovery-codes'));
        $response->assertSessionHas('url.intended', route('profile.edit'));
        $plain = session('two_factor.recovery_codes_plain');
        $this->assertCount(8, $plain);

        $user = $user->fresh();
        $this->assertSame(8, $user->recoveryCodesRemaining());
        $service = app(TwoFactorAuthenticator::class);
        $this->assertFalse($service->useRecoveryCode($user, 'aaaaa-bbbb1'));
        $this->assertTrue($service->useRecoveryCode($user, $plain[0]));
    }

    public function test_profile_lists_trusted_devices_with_summary(): void
    {
        $user = User::factory()->create();
        TrustedDevice::factory()->create(['user_id' => $user->id, 'user_agent' => self::CHROME_WINDOWS]);

        $this->actingAs($user)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Chrome di Windows');
    }

    public function test_user_can_revoke_own_device(): void
    {
        $user = User::factory()->create();
        $device = TrustedDevice::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->delete(route('profile.trusted-devices.destroy', $device->id))
            ->assertRedirect()
            ->assertSessionHas('security_status', 'perangkat-dicabut');

        $this->assertDatabaseMissing('trusted_devices', ['id' => $device->id]);
    }

    public function test_user_cannot_revoke_another_users_device(): void
    {
        $user = User::factory()->create();
        $device = TrustedDevice::factory()->create();

        $this->actingAs($user)
            ->delete(route('profile.trusted-devices.destroy', $device->id))
            ->assertNotFound();

        $this->assertDatabaseHas('trusted_devices', ['id' => $device->id]);
    }

    public function test_revoke_all_removes_every_device(): void
    {
        $user = User::factory()->create();
        $other = TrustedDevice::factory()->create();
        TrustedDevice::factory()->count(3)->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->delete(route('profile.trusted-devices.destroy-all'))
            ->assertRedirect()
            ->assertSessionHas('security_status', 'semua-perangkat-dicabut');

        $this->assertSame(0, $user->trustedDevices()->count());
        $this->assertDatabaseHas('trusted_devices', ['id' => $other->id]);
    }

    public function test_banner_shows_when_two_or_fewer_codes_remain(): void
    {
        $user = $this->userWithCodes(2);

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertSee('Recovery code hampir habis')
            ->assertSee(route('profile.edit'), false);
    }

    public function test_banner_hidden_when_more_than_two_codes_remain(): void
    {
        $user = $this->userWithCodes(3);

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertDontSee('Recovery code hampir habis');
    }
}
