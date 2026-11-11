<?php

namespace Tests\Feature\Auth\TwoFactor;

use App\Models\User;
use App\Notifications\NewLoginNotification;
use App\Notifications\RecoveryCodeUsedNotification;
use App\Notifications\TwoFactorEnabledNotification;
use App\Notifications\TwoFactorResetByAdminNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SecurityNotificationsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: Notification, 1: string}>
     */
    private function notifications(): array
    {
        return [
            'login' => [new NewLoginNotification('203.0.113.9', 'Chrome di Windows', 'Password + 2FA', now()), 'Login baru ke akun JogjaTrip kamu'],
            'enabled' => [new TwoFactorEnabledNotification, '2FA telah diaktifkan di akunmu'],
            'recovery' => [new RecoveryCodeUsedNotification(3), 'Recovery code dipakai untuk masuk'],
            'reset' => [new TwoFactorResetByAdminNotification, '2FA akunmu telah di-reset oleh Admin'],
        ];
    }

    public function test_all_notifications_are_queued_and_use_database_and_mail(): void
    {
        $user = User::factory()->create();

        foreach ($this->notifications() as $name => [$notification]) {
            $this->assertInstanceOf(ShouldQueue::class, $notification, $name);
            // Dicoba ulang dengan jeda bertahap (RetriesDelivery).
            $this->assertSame(5, $notification->tries, $name);
            $this->assertSame([30, 120, 600, 1800], $notification->backoff(), $name);
            $this->assertSame(['database', 'mail'], $notification->via($user), $name);
            $this->assertNotEmpty($notification->toArray($user)['message'], $name);
        }
    }

    public function test_mail_subjects(): void
    {
        $user = User::factory()->create();

        foreach ($this->notifications() as $name => [$notification, $subject]) {
            $this->assertSame($subject, $notification->toMail($user)->subject, $name);
        }
    }

    public function test_new_login_carries_valid_signed_not_me_link(): void
    {
        $user = User::factory()->create();
        $notification = new NewLoginNotification('203.0.113.9', 'Chrome di Windows', 'Password + 2FA', now());

        $data = $notification->toArray($user);

        $this->assertSame('Login baru dari Chrome di Windows (203.0.113.9)', $data['message']);
        $this->assertSame('Bukan saya', $data['action_label']);
        $this->assertTrue(URL::hasValidSignature(Request::create($data['action_url'])));
        $this->assertStringContainsString('/keamanan/bukan-saya/'.$user->id, $data['action_url']);
        $this->assertSame('Bukan saya', $notification->toMail($user)->actionText);
    }

    public function test_two_factor_enabled_carries_not_me_link(): void
    {
        $user = User::factory()->create();
        $data = (new TwoFactorEnabledNotification)->toArray($user);

        $this->assertTrue(URL::hasValidSignature(Request::create($data['action_url'])));
        $this->assertSame('Bukan saya', $data['action_label']);
    }

    public function test_recovery_code_message_mentions_remaining(): void
    {
        $user = User::factory()->create();

        $this->assertStringContainsString('3', (new RecoveryCodeUsedNotification(3))->toArray($user)['message']);
    }

    public function test_notifications_page_shows_action_button(): void
    {
        $user = User::factory()->create();
        $user->notify(new TwoFactorEnabledNotification);

        $this->actingAs($user)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Bukan saya')
            ->assertSee('/keamanan/bukan-saya/'.$user->id, false);
    }
}
