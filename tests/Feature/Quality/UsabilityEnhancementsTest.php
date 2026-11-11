<?php

namespace Tests\Feature\Quality;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Usability: dialog konfirmasi bersama, field error yang aksesibel,
 * banner offline, dan peringatan perubahan belum disimpan.
 */
class UsabilityEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_layout_includes_confirm_dialog_and_offline_banner(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('id="dialog-konfirmasi"', false)
            ->assertSee('id="banner-offline"', false);
    }

    public function test_offline_banner_is_hidden_by_default(): void
    {
        $html = (string) $this->get(route('home'))->getContent();

        // Class display (flex/block/grid) pada elemen ber-[hidden] mengalahkan
        // atribut hidden sehingga banner selalu tampil walau sedang online.
        $this->assertMatchesRegularExpression('/<div id="banner-offline" hidden[^>]*>/', $html);
        preg_match('/<div id="banner-offline"[^>]*class="([^"]*)"/', $html, $match);
        $this->assertDoesNotMatchRegularExpression('/(^|\s)(flex|block|grid|inline-flex)(\s|$)/', $match[1] ?? '');
    }

    public function test_views_no_longer_use_inline_javascript_confirm(): void
    {
        $offenders = collect(File::allFiles(resource_path('views')))
            ->filter(fn ($file) => str_contains($file->getContents(), 'return confirm('))
            ->map(fn ($file) => $file->getRelativePathname())
            ->values()
            ->all();

        $this->assertSame([], $offenders, 'Gunakan data-confirm="..." (dialog bersama) alih-alih onsubmit confirm().');
    }

    public function test_confirm_message_with_quotes_is_rendered_as_plain_attribute_text(): void
    {
        $html = $this->blade(
            '<form data-confirm="{{ $message }}"></form>',
            ['message' => "Keluarkan O'Brien\"><script>x</script> dari kolaborator?"]
        );

        $html->assertDontSee('<script>x</script>', false);
        $html->assertSee('data-confirm="Keluarkan O&#039;Brien&quot;&gt;&lt;script&gt;', false);
    }

    public function test_invalid_text_input_is_marked_for_assistive_technology(): void
    {
        $html = (string) $this->withViewErrors(['title' => 'Judul wajib diisi.'])
            ->blade('<x-text-input name="title" /><x-text-input name="notes" />');

        $this->assertSame(1, substr_count($html, 'aria-invalid="true"'));
        $this->assertMatchesRegularExpression('/<input[^>]*aria-invalid="true"[^>]*name="title"/', $html);
        $this->assertDoesNotMatchRegularExpression('/<input[^>]*aria-invalid="true"[^>]*name="notes"/', $html);
    }

    public function test_array_style_field_names_map_to_dotted_error_keys(): void
    {
        $this->withViewErrors(['items.0.notes' => 'Terlalu panjang.'])
            ->blade('<x-text-input name="items[0][notes]" />')
            ->assertSee('aria-invalid="true"', false);
    }

    public function test_input_error_is_tagged_so_it_can_be_linked_to_its_field(): void
    {
        $this->blade('<x-input-error :messages="[\'Wajib diisi.\']" />')
            ->assertSee('data-input-error', false);
    }

    public function test_long_forms_warn_about_unsaved_changes(): void
    {
        foreach (['itineraries/create', 'itineraries/edit', 'itineraries/ai-create', 'admin/destinations/create', 'admin/destinations/edit'] as $view) {
            $this->assertStringContainsString(
                'data-warn-unsaved',
                File::get(resource_path("views/{$view}.blade.php")),
                "Form {$view} harus memperingatkan perubahan yang belum disimpan."
            );
        }
    }
}
