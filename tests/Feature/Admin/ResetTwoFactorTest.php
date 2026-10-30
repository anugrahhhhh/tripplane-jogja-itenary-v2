<?php

namespace Tests\Feature\Admin;

use App\Models\TrustedDevice;
use App\Models\User;
use App\Notifications\TwoFactorResetByAdminNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ResetTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private function insertSession(string $id, int $userId): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $userId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => 'x',
            'last_activity' => time(),
        ]);
    }

    public function test_admin_can_reset_another_users_2fa(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create();
        $other = User::factory()->create();
        TrustedDevice::factory()->count(2)->create(['user_id' => $target->id]);
        $this->insertSession('target-session', $target->id);
        $this->insertSession('other-session', $other->id);

        $response = $this->actingAs($admin)->post(route('admin.users.reset-two-factor', $target));

        $response->assertRedirect();
        $response->assertSessionHas('status', '2FA pengguna berhasil di-reset.');

        $target->refresh();
        $this->assertNull($target->two_factor_secret);
        $this->assertNull($target->two_factor_recovery_codes);
        $this->assertNull($target->two_factor_confirmed_at);
        $this->assertNull($target->two_factor_last_used_step);
        $this->assertSame(0, $target->trustedDevices()->count());
        $this->assertDatabaseMissing('sessions', ['id' => 'target-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'other-session']);
        Notification::assertSentTo($target, TwoFactorResetByAdminNotification::class);
    }

    public function test_admin_cannot_reset_own_2fa(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.users.reset-two-factor', $admin))
            ->assertForbidden();

        $this->assertTrue($admin->fresh()->hasTwoFactorEnabled());
        Notification::assertNothingSent();
    }

    public function test_traveler_cannot_access_reset_route(): void
    {
        $traveler = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($traveler)
            ->post(route('admin.users.reset-two-factor', $target))
            ->assertForbidden();

        $this->assertTrue($target->fresh()->hasTwoFactorEnabled());
    }

    public function test_show_page_has_reset_button(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.show', $target))
            ->assertOk()
            ->assertSee(route('admin.users.reset-two-factor', $target))
            ->assertSee('2FA aktif')
            ->assertSee('Reset 2FA');

        $this->actingAs($admin)
            ->get(route('admin.users.show', $admin))
            ->assertOk()
            ->assertDontSee(route('admin.users.reset-two-factor', $admin));
    }
}
