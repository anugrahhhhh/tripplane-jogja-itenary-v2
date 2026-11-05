<?php

namespace Tests\Feature\Auth\TwoFactor;

use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class NotMeTest extends TestCase
{
    use RefreshDatabase;

    private function link(User $user, string $route = 'security.not-me'): string
    {
        return URL::temporarySignedRoute($route, now()->addHours(24), ['user' => $user->id]);
    }

    private function insertSession(string $id, ?int $userId): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $userId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'x',
            'last_activity' => time(),
        ]);
    }

    public function test_get_shows_confirmation_without_changing_anything(): void
    {
        $user = User::factory()->create();
        TrustedDevice::factory()->create(['user_id' => $user->id]);
        $this->insertSession('s1', $user->id);

        $this->get($this->link($user))->assertOk()->assertSee('Ya, amankan akun saya');

        $user->refresh();
        $this->assertTrue($user->hasTwoFactorEnabled());
        $this->assertSame(1, $user->trustedDevices()->count());
        $this->assertSame(1, DB::table('sessions')->where('user_id', $user->id)->count());
    }

    public function test_invalid_or_expired_signature_shows_expired_page(): void
    {
        $user = User::factory()->create();
        $link = $this->link($user);

        $this->travel(25)->hours();

        $this->get($link)->assertStatus(403)->assertSee('Link kedaluwarsa');
        $this->post($link)->assertStatus(403)->assertSee('Link kedaluwarsa');
        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_unsigned_url_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->post(route('security.not-me.confirm', $user))->assertStatus(403);
        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_post_secures_account(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        TrustedDevice::factory()->count(2)->create(['user_id' => $user->id]);
        $this->insertSession('s1', $user->id);
        $this->insertSession('s2', $user->id);
        $this->insertSession('s3', $other->id);
        $before = $user->remember_token;

        $this->post($this->link($user, 'security.not-me.confirm'));

        $user->refresh();
        $this->assertSame(0, DB::table('sessions')->where('user_id', $user->id)->count());
        $this->assertSame(1, DB::table('sessions')->where('user_id', $other->id)->count());
        $this->assertSame(0, $user->trustedDevices()->count());
        $this->assertFalse($user->hasTwoFactorEnabled());
        $this->assertNotSame($before, $user->remember_token);
    }

    public function test_post_shows_done_page_and_emails_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->create(['google_id' => null]);

        $this->post($this->link($user, 'security.not-me.confirm'))
            ->assertOk()
            ->assertSee('Akun kamu sudah diamankan')
            ->assertSee('Kami sudah mengirim link')
            ->assertDontSee('myaccount.google.com');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_post_does_not_redirect_to_password_reset(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $response = $this->post($this->link($user, 'security.not-me.confirm'));

        $response->assertOk();
        $this->assertNull($response->headers->get('Location'));
    }

    public function test_post_logs_out_current_user_if_same(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->post($this->link($user, 'security.not-me.confirm'))
            ->assertOk()
            ->assertSee('Akun kamu sudah diamankan');

        $this->assertGuest();
    }

    public function test_done_page_renders_even_if_another_user_is_logged_in(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($other)->post($this->link($user, 'security.not-me.confirm'))
            ->assertOk()
            ->assertSee('Akun kamu sudah diamankan');

        $this->assertAuthenticatedAs($other);
    }

    public function test_clicking_twice_still_succeeds(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $link = $this->link($user, 'security.not-me.confirm');

        $this->post($link)->assertOk()->assertSee('Akun kamu sudah diamankan');
        $this->post($link)->assertOk()->assertSee('Akun kamu sudah diamankan');
    }

    public function test_done_page_shows_google_hint_for_google_user(): void
    {
        Notification::fake();
        $user = User::factory()->create(['google_id' => 'g-123']);

        $this->post($this->link($user, 'security.not-me.confirm'))
            ->assertOk()
            ->assertSee('myaccount.google.com/security');
    }

    public function test_post_invalidates_old_password(): void
    {
        $user = User::factory()->create();

        $this->post($this->link($user, 'security.not-me.confirm'));

        $this->assertFalse(Hash::check('password', $user->fresh()->password));
    }

    public function test_deleted_user_with_valid_signature_shows_expired_page(): void
    {
        $user = User::factory()->create();
        $get = $this->link($user);
        $post = $this->link($user, 'security.not-me.confirm');
        $user->delete();

        $this->get($get)->assertStatus(403)->assertSee('Link kedaluwarsa');
        $this->post($post)->assertStatus(403)->assertSee('Link kedaluwarsa');
    }

    public function test_signature_for_another_user_is_rejected(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $query = parse_url($this->link($a), PHP_URL_QUERY);

        $this->get(route('security.not-me', $b).'?'.$query)->assertStatus(403)->assertSee('Link kedaluwarsa');
        $this->post(route('security.not-me.confirm', $b).'?'.$query)->assertStatus(403);
        $this->assertTrue($b->fresh()->hasTwoFactorEnabled());
    }
}
