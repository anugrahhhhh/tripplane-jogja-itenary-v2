<?php

namespace Tests\Feature\Auth\TwoFactor;

use App\Models\TrustedDevice;
use App\Models\User;
use App\Services\Auth\TrustedDeviceManager;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrustedDeviceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Route uji lewat grup web, supaya EncryptCookies ikut bekerja seperti di produksi.
        Route::middleware('web')->group(function () {
            Route::get('/_trust', fn (Request $r) => ['token_hash' => app(TrustedDeviceManager::class)->trust($this->user, $r)->token_hash]);
            Route::get('/_check', fn (Request $r) => ['trusted' => app(TrustedDeviceManager::class)->isTrusted($this->user, $r)]);
            Route::get('/_check-other', fn (Request $r) => ['trusted' => app(TrustedDeviceManager::class)->isTrusted(User::factory()->create(), $r)]);
        });
    }

    // withCookie() mengenkripsi nilai seperti browser menerima cookie dari EncryptCookies,
    // jadi jalur dekripsi asli ikut teruji.

    /** Menjalankan trust() lewat HTTP dan mengembalikan [token mentah, response]. */
    private function trustViaHttp(): array
    {
        $response = $this->get('/_trust');
        $cookie = collect($response->headers->getCookies())
            ->first(fn ($c) => $c->getName() === TrustedDeviceManager::COOKIE);

        $token = app('encrypter')->decrypt($cookie->getValue(), EncryptCookies::serialized(TrustedDeviceManager::COOKIE));
        $token = CookieValuePrefix::remove($token);

        return [$token, $response, $cookie];
    }

    public function test_trust_stores_only_hash_and_queues_cookie(): void
    {
        [$token, $response, $cookie] = $this->trustViaHttp();

        $device = TrustedDevice::sole();
        $this->assertSame(64, strlen($device->token_hash));
        $this->assertSame(hash('sha256', $token), $device->token_hash);
        $this->assertSame(40, strlen($token));
        $this->assertNotSame($device->token_hash, $cookie->getValue());
        $this->assertNotSame($token, $cookie->getValue());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
        $this->assertEqualsWithDelta(now()->addDays(30)->timestamp, $cookie->getExpiresTime(), 5);
        $this->assertEqualsWithDelta(now()->addDays(30)->timestamp, $device->expires_at->timestamp, 5);
        $this->assertTrue($device->user->is($this->user));
    }

    public function test_trusted_cookie_is_recognised_for_same_user(): void
    {
        [$token] = $this->trustViaHttp();

        $this->assertNull(TrustedDevice::sole()->last_used_at);

        $this->withCookie(TrustedDeviceManager::COOKIE, $token)
            ->get('/_check')
            ->assertJson(['trusted' => true]);

        $this->assertNotNull(TrustedDevice::sole()->last_used_at);
    }

    public function test_request_without_cookie_is_not_trusted(): void
    {
        $this->get('/_check')->assertJson(['trusted' => false]);
    }

    public function test_cookie_of_another_user_is_not_trusted(): void
    {
        [$token] = $this->trustViaHttp();

        $this->withCookie(TrustedDeviceManager::COOKIE, $token)
            ->get('/_check-other')
            ->assertJson(['trusted' => false]);
    }

    public function test_expired_device_is_not_trusted(): void
    {
        [$token] = $this->trustViaHttp();

        $this->travel(31)->days();

        $this->withCookie(TrustedDeviceManager::COOKIE, $token)
            ->get('/_check')
            ->assertJson(['trusted' => false]);
    }

    public function test_revoked_device_is_not_trusted(): void
    {
        [$token] = $this->trustViaHttp();

        app(TrustedDeviceManager::class)->revoke(TrustedDevice::sole());

        $this->assertDatabaseCount('trusted_devices', 0);
        $this->withCookie(TrustedDeviceManager::COOKIE, $token)
            ->get('/_check')
            ->assertJson(['trusted' => false]);
    }

    public function test_revoke_all_removes_only_that_users_devices(): void
    {
        $this->trustViaHttp();
        $this->trustViaHttp();
        TrustedDevice::factory()->count(1)->create();

        $this->assertSame(2, app(TrustedDeviceManager::class)->revokeAll($this->user));
        $this->assertDatabaseCount('trusted_devices', 1);
    }

    public function test_forged_cookie_is_not_trusted_and_cookie_is_forgotten(): void
    {
        $response = $this->withCookie(TrustedDeviceManager::COOKIE, str_repeat('a', 40))
            ->get('/_check');

        $response->assertJson(['trusted' => false]);
        $forget = collect($response->headers->getCookies())
            ->first(fn ($c) => $c->getName() === TrustedDeviceManager::COOKIE);
        $this->assertNotNull($forget);
        $this->assertLessThan(time(), $forget->getExpiresTime());
    }

    public function test_prune_command_deletes_only_expired_devices(): void
    {
        $this->trustViaHttp();
        TrustedDevice::factory()->create(['user_id' => $this->user->id, 'expires_at' => now()->subDay()]);

        $this->artisan('trusted-devices:prune')->assertSuccessful();

        $this->assertDatabaseCount('trusted_devices', 1);
        $this->assertTrue(TrustedDevice::sole()->expires_at->isFuture());
    }
}
