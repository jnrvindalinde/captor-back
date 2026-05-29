<?php

namespace App\Http\Controllers\Api\Admin;

use App\Cms\SectionRegistry;
use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Http\Resources\PageSectionResource;
use App\Models\Page;
use App\Models\PageAudit;
use App\Models\PageSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    public function index(Request $request)
    {
        $q = Page::query()->latest('updated_at');

        if ($status = $request->string('status')->toString()) {
            $q->where('status', $status);
        }
        if ($term = $request->string('q')->toString()) {
            $q->where(function ($w) use ($term) {
                $like = '%'.$term.'%';
                $w->where('title_en', 'like', $like)
                  ->orWhere('slug', 'like', $like);
            });
        }

        return PageResource::collection($q->paginate((int) $request->input('per_page', 50)));
    }

    public function show(Page $page)
    {
        $page->load(['sections' => fn ($q) => $q->orderBy('position'), 'ogImage']);
        return new PageResource($page);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slug'     => ['required', 'string', 'max:200', 'regex:/^[a-z0-9-]+$/', 'unique:pages,slug'],
            'kind'     => ['nullable', Rule::in(['marketing', 'landing'])],
            'title_en' => ['required', 'string', 'max:255'],
            'title_fr' => ['nullable', 'string', 'max:255'],
        ]);

        $page = Page::create([
            ...$data,
            'kind'       => $data['kind'] ?? 'landing',
            'status'     => Page::STATUS_DRAFT,
            'updated_by' => $request->user()?->id,
        ]);

        $this->audit($request, $page, 'create', ['slug' => $page->slug]);

        return (new PageResource($page))->response()->setStatusCode(201);
    }

    public function update(Request $request, Page $page)
    {
        $data = $request->validate([
            'slug'               => ['nullable', 'string', 'max:200', 'regex:/^[a-z0-9-]+$/', Rule::unique('pages', 'slug')->ignore($page->id)],
            'kind'               => ['nullable', Rule::in(['marketing', 'landing'])],
            'title_en'           => ['nullable', 'string', 'max:255'],
            'title_fr'           => ['nullable', 'string', 'max:255'],
            'seo_title_en'       => ['nullable', 'string', 'max:255'],
            'seo_title_fr'       => ['nullable', 'string', 'max:255'],
            'seo_description_en' => ['nullable', 'string', 'max:1000'],
            'seo_description_fr' => ['nullable', 'string', 'max:1000'],
            'og_image_id'        => ['nullable', 'integer', 'exists:media,id'],
        ]);

        $page->fill(array_filter($data, fn ($v) => $v !== null));
        $page->updated_by = $request->user()?->id;
        $page->save();

        $this->audit($request, $page, 'update', ['fields' => array_keys(array_filter($data, fn ($v) => $v !== null))]);
        $this->bustCache($page);
        return new PageResource($page->fresh(['sections', 'ogImage']));
    }

    public function destroy(Page $page): JsonResponse
    {
        $this->audit(request(), $page, 'delete', ['slug' => $page->slug]);
        $page->delete();
        $this->bustCache($page);
        return response()->json(['ok' => true]);
    }

    public function publish(Page $page)
    {
        $page->update([
            'status'       => Page::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        $this->audit(request(), $page, 'publish');
        $this->bustCache($page);
        return new PageResource($page->fresh(['sections', 'ogImage']));
    }

    public function unpublish(Page $page)
    {
        $page->update(['status' => Page::STATUS_DRAFT]);
        $this->audit(request(), $page, 'unpublish');
        $this->bustCache($page);
        return new PageResource($page->fresh(['sections', 'ogImage']));
    }

    /* ---------- Sections ---------- */

    public function storeSection(Request $request, Page $page): JsonResponse
    {
        $base = $request->validate([
            'type'     => ['required', 'string', Rule::in(SectionRegistry::types())],
            'position' => ['nullable', 'integer', 'min:0'],
            'status'   => ['nullable', Rule::in(['draft', 'published'])],
            'data'     => ['required', 'array'],
        ]);

        $request->validate(SectionRegistry::rulesFor($base['type']));

        $position = $base['position']
            ?? ((int) $page->sections()->max('position') + 1);

        $section = PageSection::create([
            'page_id'  => $page->id,
            'type'     => $base['type'],
            'position' => $position,
            'status'   => $base['status'] ?? 'published',
            'data'     => $base['data'],
        ]);

        $this->audit($request, $page, 'section_add', ['type' => $section->type, 'uuid' => $section->uuid]);
        $this->bustCache($page);
        return (new PageSectionResource($section))->response()->setStatusCode(201);
    }

    public function updateSection(Request $request, Page $page, PageSection $section)
    {
        abort_unless($section->page_id === $page->id, 404);

        $base = $request->validate([
            'position' => ['nullable', 'integer', 'min:0'],
            'status'   => ['nullable', Rule::in(['draft', 'published'])],
            'data'     => ['nullable', 'array'],
        ]);

        if (array_key_exists('data', $base)) {
            $request->validate(SectionRegistry::rulesFor($section->type));
        }

        $section->fill(array_filter($base, fn ($v) => $v !== null));
        $section->save();

        $this->audit($request, $page, 'section_update', ['type' => $section->type, 'uuid' => $section->uuid, 'fields' => array_keys($base)]);
        $this->bustCache($page);
        return new PageSectionResource($section->fresh());
    }

    public function destroySection(Page $page, PageSection $section): JsonResponse
    {
        abort_unless($section->page_id === $page->id, 404);
        $this->audit(request(), $page, 'section_delete', ['type' => $section->type, 'uuid' => $section->uuid]);
        $section->delete();
        $this->bustCache($page);
        return response()->json(['ok' => true]);
    }

    public function reorderSections(Request $request, Page $page): JsonResponse
    {
        $data = $request->validate([
            'order'             => ['required', 'array', 'min:1'],
            'order.*.uuid'      => ['required', 'uuid'],
            'order.*.position'  => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($page, $data) {
            foreach ($data['order'] as $row) {
                $page->sections()
                    ->where('uuid', $row['uuid'])
                    ->update(['position' => $row['position']]);
            }
        });

        $this->audit($request, $page, 'sections_reorder', ['count' => count($data['order'])]);
        $this->bustCache($page);
        return response()->json(['ok' => true]);
    }

    public function registry(): JsonResponse
    {
        return response()->json(['data' => SectionRegistry::all()]);
    }

    private function bustCache(Page $page): void
    {
        Cache::forget("cms.page.{$page->slug}");
    }

    /**
     * Append an audit entry tying the action to the current admin.
     */
    private function audit(Request $request, Page $page, string $action, array $payload = []): void
    {
        PageAudit::create([
            'page_id'    => $page->id,
            'user_id'    => $request->user()?->id,
            'action'     => $action,
            'payload'    => $payload ?: null,
            'created_at' => now(),
        ]);
    }

    /**
     * GET /api/admin/pages/{page}/audits — last 100 audit rows for a page.
     */
    public function audits(Page $page): JsonResponse
    {
        $rows = $page->audits()
            ->with('user:id,name,email')
            ->latest('created_at')
            ->limit(100)
            ->get()
            ->map(fn (PageAudit $a) => [
                'id'         => $a->id,
                'action'     => $a->action,
                'payload'    => $a->payload,
                'user'       => $a->user ? ['id' => $a->user->id, 'name' => $a->user->name, 'email' => $a->user->email] : null,
                'created_at' => $a->created_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $rows]);
    }
}
