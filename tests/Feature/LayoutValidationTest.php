<?php

namespace Tests\Feature;

use App\Models\LayoutItem;
use App\Models\LayoutSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_layout_item_cannot_move_below_its_descendant(): void
    {
        $publisher = User::factory()->create(['role' => 'publisher']);
        $section = LayoutSection::create(['area' => 'home', 'key' => 'services', 'type' => 'services', 'is_active' => true]);
        $root = LayoutItem::create(['layout_section_id' => $section->id, 'label_bn' => 'Root', 'is_active' => true]);
        $child = LayoutItem::create(['layout_section_id' => $section->id, 'parent_id' => $root->id, 'label_bn' => 'Child', 'is_active' => true]);

        $this->actingAs($publisher)->put(route('admin.layout-items.update', $root), [
            'parent_id' => $child->id,
            'label_bn' => 'Root',
            'sort_order' => 0,
            'is_active' => 1,
        ])->assertSessionHasErrors('parent_id');

        $this->assertNull($root->fresh()->parent_id);
    }

    public function test_layout_item_rejects_unsafe_urls(): void
    {
        $publisher = User::factory()->create(['role' => 'publisher']);
        $section = LayoutSection::create(['area' => 'home', 'key' => 'services', 'type' => 'services', 'is_active' => true]);

        $this->actingAs($publisher)->post(route('admin.layouts.items.store', $section), [
            'label_bn' => 'Unsafe',
            'url' => '//evil.example/path',
            'sort_order' => 0,
            'is_active' => 1,
        ])->assertSessionHasErrors('url');

        $this->assertDatabaseMissing('layout_items', ['label_bn' => 'Unsafe']);
    }
}
