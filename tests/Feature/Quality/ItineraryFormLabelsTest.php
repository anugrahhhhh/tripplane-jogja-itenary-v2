<?php

namespace Tests\Feature\Quality;

use App\Models\Destination;
use App\Models\Itinerary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Setiap kolom form di halaman itinerary harus punya label yang terhubung,
 * supaya terbaca screen reader dan tidak hilang saat pengguna mengetik.
 */
class ItineraryFormLabelsTest extends TestCase
{
    use RefreshDatabase;

    private function ownerPageHtml(): array
    {
        $itinerary = Itinerary::factory()->create();
        $destination = Destination::factory()->create();
        $item = $itinerary->items()->create([
            'destination_id' => $destination->id,
            'day_number' => 1,
            'start_time' => '09:00',
            'duration_minutes' => 60,
            'sort_order' => 0,
        ]);
        $itinerary->forceFill(['share_token' => str_repeat('b', 40)])->save();

        $html = $this->actingAs($itinerary->user)
            ->get(route('itineraries.show', $itinerary))
            ->assertOk()
            ->getContent();

        return [$html, $item];
    }

    public function test_every_visible_field_has_a_connected_label(): void
    {
        [$html] = $this->ownerPageHtml();

        $doc = new \DOMDocument;
        libxml_use_internal_errors(true);
        $doc->loadHTML($html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($doc);

        $labelFor = [];
        foreach ($xpath->query('//label[@for]') as $label) {
            $labelFor[$label->getAttribute('for')] = true;
        }

        $unlabelled = [];
        foreach ($xpath->query('//main//input | //main//select | //main//textarea') as $field) {
            /** @var \DOMElement $field */
            if (in_array($field->getAttribute('type'), ['hidden', 'submit', 'button'], true)) {
                continue;
            }
            if ($field->hasAttribute('aria-label') || $field->hasAttribute('aria-labelledby') || $field->hasAttribute(':aria-label')) {
                continue;
            }
            if ($field->getAttribute('id') !== '' && isset($labelFor[$field->getAttribute('id')])) {
                continue;
            }
            for ($parent = $field->parentNode; $parent instanceof \DOMElement; $parent = $parent->parentNode) {
                if ($parent->nodeName === 'label') {
                    continue 2;
                }
            }

            $unlabelled[] = $field->getAttribute('name') ?: $field->getAttribute('id') ?: $field->nodeName;
        }

        $this->assertSame([], $unlabelled, 'Kolom tanpa label: '.implode(', ', $unlabelled));
    }

    public function test_ids_on_the_page_are_unique(): void
    {
        [$html] = $this->ownerPageHtml();

        preg_match_all('/\sid="([^"]+)"/', $html, $matches);
        $duplicates = array_keys(array_filter(array_count_values($matches[1]), fn ($n) => $n > 1));

        $this->assertSame([], $duplicates, 'ID ganda: '.implode(', ', $duplicates));
    }

    public function test_item_edit_and_add_forms_have_visible_labels(): void
    {
        [$html, $item] = $this->ownerPageHtml();

        foreach (['day', 'start', 'duration', 'notes'] as $field) {
            $this->assertStringContainsString('for="item-'.$item->id.'-'.$field.'"', $html);
            $this->assertStringContainsString('id="item-'.$item->id.'-'.$field.'"', $html);
        }

        foreach (['destination', 'start', 'duration', 'notes'] as $field) {
            $this->assertStringContainsString('for="add-1-'.$field.'"', $html);
        }

        $this->assertStringContainsString('for="collaborator-email"', $html);
        $this->assertStringContainsString('aria-label="Link berbagi itinerary"', $html);
    }

    public function test_collaborator_email_error_is_announced(): void
    {
        $itinerary = Itinerary::factory()->create();

        $this->actingAs($itinerary->user)
            ->from(route('itineraries.show', $itinerary))
            ->post(route('itinerary-collaborators.store', $itinerary), ['email' => 'tidak-terdaftar@example.test']);

        $this->get(route('itineraries.show', $itinerary))
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('aria-describedby="collaborator-email-error"', false)
            ->assertSee('id="collaborator-email-error"', false);
    }

    public function test_forms_still_submit_with_the_same_field_names(): void
    {
        $itinerary = Itinerary::factory()->create();
        $destination = Destination::factory()->create();

        $this->actingAs($itinerary->user)
            ->post(route('itinerary-items.store', $itinerary), [
                'day_number' => 1,
                'destination_id' => $destination->id,
                'start_time' => '10:00',
                'duration_minutes' => 90,
                'notes' => 'Bawa topi',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('itinerary_items', [
            'itinerary_id' => $itinerary->id,
            'destination_id' => $destination->id,
            'duration_minutes' => 90,
            'notes' => 'Bawa topi',
        ]);
    }
}
