<?php

namespace Tests\Feature\Quality;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IndonesianTranslationTest extends TestCase
{
    use RefreshDatabase;

    public static function languageFiles(): array
    {
        return [
            'validation' => ['validation'],
            'auth' => ['auth'],
            'passwords' => ['passwords'],
            'pagination' => ['pagination'],
        ];
    }

    /**
     * Kunci bahasa Indonesia harus lengkap dibanding bawaan framework, supaya
     * tidak ada pesan yang tiba-tiba muncul dalam bahasa Inggris.
     */
    #[DataProvider('languageFiles')]
    public function test_every_framework_key_is_translated(string $file): void
    {
        $english = require base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/'.$file.'.php');
        $indonesian = require lang_path('id/'.$file.'.php');

        // "custom" dan "attributes" berisi contoh/kosong di file bawaan;
        // isinya memang khusus aplikasi, bukan terjemahan wajib.
        $ignore = fn (string $key) => in_array(strtok($key, '.'), ['custom', 'attributes'], true);
        $englishKeys = array_filter(array_keys(Arr::dot($english)), fn ($k) => ! $ignore($k));
        $indonesianKeys = array_keys(Arr::dot($indonesian));

        $missing = array_values(array_diff($englishKeys, $indonesianKeys));

        $this->assertSame([], $missing, 'Belum diterjemahkan: '.implode(', ', $missing));
    }

    #[DataProvider('languageFiles')]
    public function test_placeholders_are_kept(string $file): void
    {
        $english = Arr::dot(require base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/'.$file.'.php'));
        $indonesian = Arr::dot(require lang_path('id/'.$file.'.php'));

        foreach ($english as $key => $text) {
            if (! isset($indonesian[$key]) || ! is_string($text)) {
                continue;
            }

            preg_match_all('/:([a-z_]+)/i', $text, $en);
            preg_match_all('/:([a-z_]+)/i', $indonesian[$key], $id);
            $normalize = fn (array $names) => array_values(array_unique(array_map('strtolower', $names)));

            $this->assertEqualsCanonicalizing($normalize($en[1]), $normalize($id[1]), "Placeholder berbeda di {$file}.{$key}");
        }
    }

    public function test_app_locale_is_indonesian(): void
    {
        $this->assertSame('id', app()->getLocale());
    }

    public function test_register_errors_are_in_indonesian_with_friendly_field_names(): void
    {
        $this->from('/register')->post('/register', [])->assertSessionHasErrors();

        $errors = session('errors');
        $this->assertSame('Kolom nama wajib diisi.', $errors->first('name'));
        $this->assertSame('Kolom email wajib diisi.', $errors->first('email'));
        $this->assertSame('Kolom kata sandi wajib diisi.', $errors->first('password'));
    }

    public function test_password_rule_messages_are_in_indonesian(): void
    {
        $this->from('/register')->post('/register', [
            'name' => 'Uji',
            'email' => 'uji@example.test',
            'password' => 'hanyahuruf',
            'password_confirmation' => 'hanyahuruf',
        ]);

        $this->assertSame('Kolom kata sandi harus berisi setidaknya satu angka.', session('errors')->first('password'));
    }

    public function test_login_failure_is_in_indonesian_and_does_not_reveal_which_part_is_wrong(): void
    {
        $user = User::factory()->create();

        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'salah-total-1']);
        $wrongPassword = session('errors')->first('email');

        $this->from('/login')->post('/login', ['email' => 'tidak-ada@example.test', 'password' => 'salah-total-1']);
        $unknownEmail = session('errors')->first('email');

        $this->assertSame('Email atau kata sandi salah.', $wrongPassword);
        $this->assertSame($wrongPassword, $unknownEmail);
    }

    public function test_english_text_never_leaks_into_common_errors(): void
    {
        $this->from('/register')->post('/register', ['email' => 'bukan-email', 'password' => 'a', 'password_confirmation' => 'b']);

        foreach (session('errors')->all() as $message) {
            $this->assertDoesNotMatchRegularExpression('/\b(The|field|must|required)\b/', $message, $message);
        }
    }
}
