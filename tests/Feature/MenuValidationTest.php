<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_item_cannot_use_a_descendant_as_its_parent(): void
    {
        $publisher = User::factory()->create(['role' => 'publisher']);
        $menu = Menu::create(['location' => 'primary', 'name_bn' => 'Primary', 'is_active' => true]);
        $root = MenuItem::create(['menu_id' => $menu->id, 'label_bn' => 'Root', 'target' => '_self', 'is_active' => true]);
        $child = MenuItem::create(['menu_id' => $menu->id, 'parent_id' => $root->id, 'label_bn' => 'Child', 'target' => '_self', 'is_active' => true]);

        $this->actingAs($publisher)->put(route('admin.menu-items.update', $root), [
            'parent_id' => $child->id,
            'label_bn' => 'Root',
            'target' => '_self',
            'sort_order' => 0,
            'is_active' => 1,
        ])->assertSessionHasErrors('parent_id');

        $this->assertNull($root->fresh()->parent_id);
    }

    public function test_menu_rejects_script_urls(): void
    {
        $publisher = User::factory()->create(['role' => 'publisher']);
        $menu = Menu::create(['location' => 'primary', 'name_bn' => 'Primary', 'is_active' => true]);

        $this->actingAs($publisher)->post(route('admin.menus.items.store', $menu), [
            'label_bn' => 'Unsafe',
            'url' => 'javascript:alert(1)',
            'target' => '_self',
            'sort_order' => 0,
            'is_active' => 1,
        ])->assertSessionHasErrors('url');

        $this->assertDatabaseMissing('menu_items', ['label_bn' => 'Unsafe']);
    }
}
