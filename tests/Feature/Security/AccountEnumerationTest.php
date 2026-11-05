<?php

namespace Tests\Feature\Security;

use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Form lupa password tidak boleh membocorkan apakah sebuah email terdaftar.
 */
class AccountEnumerationTest extends TestCase
{
    use RefreshDatabase;

    private function requestReset(string $email): array
    {
        $response = $this->from('/forgot-password')->post('/forgot-password', ['email' => $email]);

        return [
            'status' => $response->getStatusCode(),
            'redirect' => $response->headers->get('Location'),
            'flash' => session('status'),
            'errors' => session('errors')?->all() ?? [],
        ];
    }

    public function test_registered_and_unknown_emails_get_identical_responses(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $registered = $this->requestReset($user->email);
        $unknown = $this->requestReset('tidak-terdaftar@example.test');

        $this->assertSame($registered, $unknown);
        $this->assertSame(PasswordResetLinkController::GENERIC_STATUS, $registered['flash']);
        $this->assertSame([], $registered['errors']);

        Notification::assertSentToTimes($user, ResetPassword::class, 1);
    }

    public function test_repeat_request_within_a_minute_looks_the_same(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $first = $this->requestReset($user->email);
        $second = $this->requestReset($user->email);

        // Permintaan kedua di-throttle diam-diam: tidak ada email baru, tapi
        // jawabannya tetap sama (throttle hanya terjadi untuk akun yang ada).
        $this->assertSame($first, $second);
        Notification::assertSentToTimes($user, ResetPassword::class, 1);
    }

    public function test_invalid_email_format_still_shows_validation_error(): void
    {
        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'bukan-email'])
            ->assertSessionHasErrors('email');
    }
}
