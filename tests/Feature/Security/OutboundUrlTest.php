<?php

namespace Tests\Feature\Security;

use App\Models\Itinerary;
use App\Models\User;
use App\Notifications\ItineraryReminderNotification;
use App\Notifications\NewLoginNotification;
use App\Notifications\TwoFactorEnabledNotification;
use App\Support\OutboundUrl;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

/**
 * Link di email/notifikasi tidak boleh mengikuti header Host dari penyerang
 * (password reset poisoning).
 */
class OutboundUrlTest extends TestCase
{
    use RefreshDatabase;

    private const APP_URL = 'https://jogjatrip.example';

    private const ATTACKER = 'penyerang.example';

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => self::APP_URL]);
    }

    /**
     * Buat URL generator seolah-olah sedang melayani request dengan Host palsu.
     */
    private function pretendRequestFromAttackerHost(): void
    {
        $this->app['url']->setRequest(Request::create('http://'.self::ATTACKER.'/forgot-password', 'POST'));
    }

    private function notificationUrls(object $notification, object $notifiable): array
    {
        $mail = $notification->toMail($notifiable);
        $urls = [$mail->actionUrl];

        if (method_exists($notification, 'toArray')) {
            $urls[] = $notification->toArray($notifiable)['action_url'] ?? null;
        }

        return array_values(array_filter($urls));
    }

    public function test_password_reset_email_ignores_spoofed_host(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->withServerVariables(['HTTP_HOST' => self::ATTACKER])
            ->post('http://'.self::ATTACKER.'/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $url = $notification->toMail($user)->actionUrl;

            $this->assertStringStartsWith(self::APP_URL.'/reset-password/', $url);
            $this->assertStringNotContainsString(self::ATTACKER, $url);

            return true;
        });
    }

    public function test_email_verification_link_ignores_spoofed_host(): void
    {
        $user = User::factory()->unverified()->create();
        $this->pretendRequestFromAttackerHost();

        $url = (new VerifyEmail)->toMail($user)->actionUrl;

        $this->assertStringStartsWith(self::APP_URL.'/verify-email/', $url);
        $this->assertStringNotContainsString(self::ATTACKER, $url);
    }

    public function test_every_app_notification_link_ignores_spoofed_host(): void
    {
        $user = User::factory()->create();
        $itinerary = Itinerary::factory()->for($user)->create();
        $this->pretendRequestFromAttackerHost();

        $notifications = [
            new NewLoginNotification('10.0.0.1', 'Chrome di Windows', 'Kata sandi', now()),
            new TwoFactorEnabledNotification,
            new ItineraryReminderNotification($itinerary),
        ];

        foreach ($notifications as $notification) {
            $urls = $this->notificationUrls($notification, $user);
            $this->assertNotEmpty($urls, $notification::class.' tidak punya link');

            foreach ($urls as $url) {
                $this->assertStringStartsWith(self::APP_URL.'/', $url, $notification::class);
                $this->assertStringNotContainsString(self::ATTACKER, $url, $notification::class);
            }
        }
    }

    public function test_signed_not_me_link_from_email_is_valid_on_the_real_site(): void
    {
        $user = User::factory()->create();
        $this->pretendRequestFromAttackerHost();

        $url = (new TwoFactorEnabledNotification)->toMail($user)->actionUrl;

        $this->get($url)->assertOk();
    }

    public function test_normal_browsing_still_uses_the_host_being_visited(): void
    {
        // Situs dibuka lewat alamat lain (mis. IP server): link di halaman
        // tetap mengikuti alamat itu supaya sesi/login tidak putus.
        $this->get('http://192.168.1.50/')
            ->assertOk()
            ->assertSee('http://192.168.1.50/katalog', false);
    }

    public function test_url_settings_are_restored_after_use_even_on_exception(): void
    {
        $this->pretendRequestFromAttackerHost();
        URL::forceScheme('http');

        try {
            OutboundUrl::using(function () {
                $this->assertStringStartsWith(self::APP_URL, url('/x'));

                throw new RuntimeException('gagal di tengah');
            });
        } catch (RuntimeException) {
            // diharapkan
        }

        $this->assertSame('http://'.self::ATTACKER.'/x', url('/x'));
    }

    public function test_nested_use_restores_correctly(): void
    {
        $this->pretendRequestFromAttackerHost();

        $inner = OutboundUrl::using(fn () => OutboundUrl::using(fn () => url('/a')).'|'.url('/b'));

        $this->assertSame(self::APP_URL.'/a|'.self::APP_URL.'/b', $inner);
        $this->assertSame('http://'.self::ATTACKER.'/c', url('/c'));
    }

    public function test_invalid_app_url_falls_back_without_breaking_email(): void
    {
        config(['app.url' => 'bukan url']);
        $this->pretendRequestFromAttackerHost();

        $this->assertNull(OutboundUrl::root());
        $this->assertSame('http://'.self::ATTACKER.'/x', OutboundUrl::using(fn () => url('/x')));
    }

    public function test_reset_url_callback_is_registered(): void
    {
        $method = new ReflectionMethod(ResetPassword::class, 'resetUrl');
        $user = User::factory()->create();
        $this->pretendRequestFromAttackerHost();

        $url = $method->invoke(new ResetPassword('token-uji'), $user);

        $this->assertSame(self::APP_URL.'/reset-password/token-uji?email='.urlencode($user->email), $url);
    }
}
