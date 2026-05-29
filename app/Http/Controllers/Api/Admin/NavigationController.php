<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\NavigationItemResource;
use App\Http\Resources\NavigationMenuResource;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class NavigationController extends Controller
{
    public function index()
    {
        return NavigationMenuResource::collection(
            NavigationMenu::withCount('items')->orderBy('slug')->get(),
        );
    }

    public function show(NavigationMenu $menu)
    {
        $menu->load(['items' => fn ($q) => $q->orderBy('sort_order')]);
        return new NavigationMenuResource($menu);
    }

    public function storeItem(Request $request, NavigationMenu $menu): JsonResponse
    {
        $data = $request->validate([
            'label_en'  => ['required', 'string', 'max:255'],
            'label_fr'  => ['nullable', 'string', 'max:255'],
            'href'      => ['required', 'string', 'max:1024'],
            'target'    => ['nullable', Rule::in(['_self', '_blank'])],
            'visible'   => ['nullable', 'boolean'],
        ]);

        $item = NavigationItem::create([
            'menu_id'    => $menu->id,
            'sort_order' => (int) $menu->items()->max('sort_order') + 1,
            'visible'    => $data['visible'] ?? true,
            'target'     => $data['target'] ?? '_self',
            'label_en'   => $data['label_en'],
            'label_fr'   => $data['label_fr'] ?? null,
            'href'       => $data['href'],
        ]);

        $this->bust($menu);
        return (new NavigationItemResource($item))->response()->setStatusCode(201);
    }

    public function updateItem(Request $request, NavigationMenu $menu, NavigationItem $item)
    {
        abort_unless($item->menu_id === $menu->id, 404);

        $data = $request->validate([
            'label_en' => ['nullable', 'string', 'max:255'],
            'label_fr' => ['nullable', 'string', 'max:255'],
            'href'     => ['nullable', 'string', 'max:1024'],
            'target'   => ['nullable', Rule::in(['_self', '_blank'])],
            'visible'  => ['nullable', 'boolean'],
        ]);

        $item->fill(array_filter($data, fn ($v) => $v !== null));
        $item->save();

        $this->bust($menu);
        return new NavigationItemResource($item->fresh());
    }

    public function destroyItem(NavigationMenu $menu, NavigationItem $item): JsonResponse
    {
        abort_unless($item->menu_id === $menu->id, 404);
        $item->delete();
        $this->bust($menu);
        return response()->json(['ok' => true]);
    }

    public function reorder(Request $request, NavigationMenu $menu): JsonResponse
    {
        $data = $request->validate([
            'order'             => ['required', 'array', 'min:1'],
            'order.*.uuid'      => ['required', 'uuid'],
            'order.*.sort_order'=> ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($menu, $data) {
            foreach ($data['order'] as $row) {
                $menu->items()
                    ->where('uuid', $row['uuid'])
                    ->update(['sort_order' => $row['sort_order']]);
            }
        });

        $this->bust($menu);
        return response()->json(['ok' => true]);
    }

    private function bust(NavigationMenu $menu): void
    {
        Cache::forget("cms.menu.{$menu->slug}");
    }
}
