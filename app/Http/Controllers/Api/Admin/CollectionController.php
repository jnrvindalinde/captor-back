<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CollectionItemResource;
use App\Http\Resources\CollectionResource;
use App\Models\Collection;
use App\Models\CollectionItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CollectionController extends Controller
{
    public function index()
    {
        $collections = Collection::query()
            ->withCount('items')
            ->orderBy('name')
            ->get();

        return CollectionResource::collection($collections);
    }

    public function show(Collection $collection)
    {
        $collection->load(['items' => fn ($q) => $q->orderBy('position')]);

        return new CollectionResource($collection);
    }

    public function storeItem(Request $request, Collection $collection): JsonResponse
    {
        $payload = $request->validate([
            'data'     => ['required', 'array'],
            'position' => ['nullable', 'integer', 'min:0'],
            'status'   => ['nullable', 'in:draft,published'],
        ]);

        $this->validateAgainstSchema($collection, $payload['data']);

        $position = $payload['position']
            ?? ((int) $collection->items()->max('position') + 1);

        $item = CollectionItem::create([
            'collection_id' => $collection->id,
            'position'      => $position,
            'status'        => $payload['status'] ?? CollectionItem::STATUS_PUBLISHED,
            'data'          => $payload['data'],
            'updated_by'    => $request->user()?->id,
        ]);

        Cache::forget("cms.collection.{$collection->slug}");

        return (new CollectionItemResource($item))->response()->setStatusCode(201);
    }

    public function updateItem(Request $request, Collection $collection, CollectionItem $item)
    {
        $this->ensureItemBelongsTo($collection, $item);

        $payload = $request->validate([
            'data'     => ['nullable', 'array'],
            'position' => ['nullable', 'integer', 'min:0'],
            'status'   => ['nullable', 'in:draft,published'],
        ]);

        if (array_key_exists('data', $payload)) {
            $this->validateAgainstSchema($collection, $payload['data']);
        }

        $item->fill(array_filter($payload, fn ($v) => $v !== null));
        $item->updated_by = $request->user()?->id;
        $item->save();

        return new CollectionItemResource($item->fresh());
    }

    public function destroyItem(Collection $collection, CollectionItem $item): JsonResponse
    {
        $this->ensureItemBelongsTo($collection, $item);
        $item->delete();

        return response()->json(['ok' => true]);
    }

    public function reorder(Request $request, Collection $collection): JsonResponse
    {
        $payload = $request->validate([
            'order'           => ['required', 'array', 'min:1'],
            'order.*.uuid'    => ['required', 'uuid'],
            'order.*.position' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($collection, $payload) {
            foreach ($payload['order'] as $row) {
                $collection->items()
                    ->where('uuid', $row['uuid'])
                    ->update(['position' => $row['position']]);
            }
        });

        Cache::forget("cms.collection.{$collection->slug}");

        return response()->json(['ok' => true]);
    }

    private function ensureItemBelongsTo(Collection $collection, CollectionItem $item): void
    {
        abort_unless($item->collection_id === $collection->id, 404);
        Cache::forget("cms.collection.{$collection->slug}");
    }

    /**
     * Minimal schema validation: enforce required fields. The schema array
     * shape is `[{key: string, type: string, required?: bool}, ...]`.
     */
    private function validateAgainstSchema(Collection $collection, array $data): void
    {
        $schema = $collection->schema ?? [];
        $errors = [];

        foreach ($schema as $field) {
            $key = $field['key'] ?? null;
            if (! $key) continue;
            $required = (bool) ($field['required'] ?? false);
            if ($required && empty($data[$key])) {
                $errors["data.{$key}"] = ["The {$key} field is required."];
            }
        }

        if ($errors) {
            abort(response()->json([
                'message' => 'Validation failed.',
                'errors'  => $errors,
            ], 422));
        }
    }
}
