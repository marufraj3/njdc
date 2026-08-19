<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentItem;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Rules\SafeUrl;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $menus = Menu::orderBy('location')->get();
        $selected = $request->menu ? Menu::find($request->menu) : $menus->first();

        return view('admin.menus.index', [
            'menus' => $menus,
            'selected' => $selected,
            'allItems' => $selected
                ? MenuItem::where('menu_id', $selected->id)->orderBy('sort_order')->get()
                : collect(),
            'pages' => Page::published()->orderBy('title_bn')->get(['id', 'title_bn']),
            'content' => ContentItem::published()->orderBy('title_bn')->get(['id', 'title_bn']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'location' => 'required|alpha_dash|max:64|unique:menus,location',
            'name_bn' => 'required|max:255',
            'name_en' => 'nullable|max:255',
        ]);
        $data['is_active'] = true;
        Menu::create($data);

        return back()->with('status', 'মেনু তৈরি হয়েছে।');
    }

    public function update(Request $request, Menu $menu)
    {
        $data = $request->validate([
            'location' => ['required', 'alpha_dash', 'max:64', Rule::unique('menus')->ignore($menu)],
            'name_bn' => 'required|max:255',
            'name_en' => 'nullable|max:255',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $menu->update($data);

        return back()->with('status', 'মেনু হালনাগাদ হয়েছে।');
    }

    public function storeItem(Request $request, Menu $menu)
    {
        $data = $this->itemData($request, $menu);
        $data['menu_id'] = $menu->id;
        MenuItem::create($data);

        return back()->with('status', 'মেনু আইটেম যোগ হয়েছে।');
    }

    public function updateItem(Request $request, MenuItem $item)
    {
        $item->update($this->itemData($request, $item->menu, $item));

        return back()->with('status', 'আইটেম হালনাগাদ হয়েছে।');
    }

    public function destroyItem(MenuItem $item)
    {
        $item->delete();

        return back()->with('status', 'আইটেম মুছে ফেলা হয়েছে।');
    }

    private function itemData(Request $request, Menu $menu, ?MenuItem $item = null): array
    {
        $data = $request->validate([
            'parent_id' => ['nullable', Rule::exists('menu_items', 'id')->where('menu_id', $menu->id)],
            'label_bn' => 'required|max:255',
            'label_en' => 'nullable|max:255',
            'url' => ['nullable', 'max:2000', new SafeUrl],
            'page_id' => 'nullable|exists:pages,id',
            'content_item_id' => 'nullable|exists:content_items,id',
            'target' => ['required', Rule::in(['_self', '_blank'])],
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($item && filled($data['parent_id'] ?? null)) {
            $parentId = (int) $data['parent_id'];
            if ($parentId === $item->id || $this->descendantIds($item)->contains($parentId)) {
                throw ValidationException::withMessages([
                    'parent_id' => 'নিজের বা অধস্তন কোনো আইটেমকে প্যারেন্ট করা যাবে না।',
                ]);
            }
        }

        $data['is_active'] = $request->boolean('is_active');
        if ($data['page_id'] ?? null) {
            $data['url'] = null;
            $data['content_item_id'] = null;
        } elseif ($data['content_item_id'] ?? null) {
            $data['url'] = null;
        }

        return $data;
    }

    private function descendantIds(MenuItem $item)
    {
        $descendants = collect();
        $frontier = collect([$item->id]);

        while ($frontier->isNotEmpty()) {
            $frontier = MenuItem::query()
                ->where('menu_id', $item->menu_id)
                ->whereIn('parent_id', $frontier)
                ->pluck('id');
            $newIds = $frontier->diff($descendants);
            if ($newIds->isEmpty()) {
                break;
            }
            $descendants = $descendants->merge($newIds);
            $frontier = $newIds;
        }

        return $descendants;
    }
}
