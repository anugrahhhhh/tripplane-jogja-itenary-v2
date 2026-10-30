<?php

namespace Tests\Feature\Auth\TwoFactor;

use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
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

    public function test_post_redirects_to_password_reset_with_valid_token(): void
    {
        $user = User::factory()->create();

        $response = $this->post($this->link($user, 'security.not-me.confirm'));

        $location = $response->headers->get('Location');
        $this->assertStringStartsWith(url('/reset-password'), $location);
        $token = basename(parse_url($location, PHP_URL_PATH));
        $this->assertStringContainsString('email='.urlencode($user->email), $location);
        fwrite(STDERR, "LOC=$location TOKEN=$token
");
        $this->assertTrue(Password::tokenExists($user->fresh(), $token));
    }

    public function test_post_logs_out_current_user_if_same(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post($this->link($user, 'security.not-me.confirm'))
            ->assertRedirectContains('/reset-password/');

        $this->assertGuest();
    }

    public function test_clicking_twice_still_succeeds(): void
    {
        $user = User::factory()->create();
        $link = $this->link($user, 'security.not-me.confirm');

        $this->post($link)->assertRedirectContains('/reset-password/');
        $this->post($link)->assertRedirectContains('/reset-password/');
    }

    public function test_reset_page_shows_google_hint_when_flagged(): void
    {
        $user = User::factory()->create(['google_id' => 'g-123']);

        $this->post($this->link($user, 'security.not-me.confirm'))
            ->assertSessionHas('google_hint', true);

        $this->get($this->post($this->link($user, 'security.not-me.confirm'))->headers->get('Location'))
            ->assertOk()
            ->assertSee('akun Google');
    }

    public function test_reset_page_has_no_google_hint_for_regular_user(): void
    {
        $user = User::factory()->create(['google_id' => null]);

        $this->post($this->link($user, 'security.not-me.confirm'))
            ->assertSessionHas('google_hint', false);
    }
}
