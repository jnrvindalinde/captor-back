<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use App\Services\CloudinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MediaController extends Controller
{
    public function __construct(private CloudinaryService $cloudinary) {}

    public function index(Request $request)
    {
        $request->validate([
            'q'        => ['nullable', 'string', 'max:200'],
            'folder'   => ['nullable', 'string', 'max:200'],
            'format'   => ['nullable', 'string', 'max:16'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $query = Media::query()->latest('id');

        if ($q = $request->string('q')->toString()) {
            $like = '%'.$q.'%';
            $query->where(function ($w) use ($like) {
                $w->where('original_filename', 'like', $like)
                  ->orWhere('public_id', 'like', $like)
                  ->orWhere('alt_en', 'like', $like)
                  ->orWhere('alt_fr', 'like', $like);
            });
        }

        if ($folder = $request->string('folder')->toString()) {
            $query->where('folder', $folder);
        }

        if ($format = $request->string('format')->toString()) {
            $query->where('format', $format);
        }

        $perPage = (int) $request->input('per_page', 60);

        return MediaResource::collection($query->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file'    => ['required', 'file', 'max:25600'], // 25 MB
            'folder'  => ['nullable', 'string', 'max:200'],
            'alt_en'  => ['nullable', 'string', 'max:255'],
            'alt_fr'  => ['nullable', 'string', 'max:255'],
        ]);

        if (! $this->cloudinary->isConfigured()) {
            return response()->json([
                'message' => 'Cloudinary is not configured on the server.',
            ], 503);
        }

        $folder = $request->input('folder') ?: 'captor';

        $result = $this->cloudinary->upload($request->file('file'), $folder);

        $media = Media::create([
            'provider'          => 'cloudinary',
            'public_id'         => (string) ($result['public_id'] ?? ''),
            'secure_url'        => (string) ($result['secure_url'] ?? $result['url'] ?? ''),
            'format'            => $result['format'] ?? null,
            'width'             => $result['width'] ?? null,
            'height'            => $result['height'] ?? null,
            'bytes'             => $result['bytes'] ?? null,
            'original_filename' => $request->file('file')->getClientOriginalName(),
            'folder'            => $result['folder'] ?? $folder,
            'alt_en'            => $request->input('alt_en'),
            'alt_fr'            => $request->input('alt_fr'),
            'meta'              => [
                'resource_type' => $result['resource_type'] ?? null,
                'version'       => $result['version'] ?? null,
                'etag'          => $result['etag'] ?? null,
            ],
            'uploaded_by' => $request->user()?->id,
        ]);

        return (new MediaResource($media))->response()->setStatusCode(201);
    }

    public function show(Media $medium)
    {
        return new MediaResource($medium);
    }

    public function update(Request $request, Media $medium)
    {
        $data = $request->validate([
            'alt_en'     => ['nullable', 'string', 'max:255'],
            'alt_fr'     => ['nullable', 'string', 'max:255'],
            'caption_en' => ['nullable', 'string', 'max:2000'],
            'caption_fr' => ['nullable', 'string', 'max:2000'],
        ]);

        $medium->update($data);

        return new MediaResource($medium->fresh());
    }

    public function destroy(Media $medium): JsonResponse
    {
        // TODO (CMS phase 5+): refuse delete when referenced by any page_section / collection_item / global.

        if ($medium->provider === 'cloudinary' && $this->cloudinary->isConfigured()) {
            try {
                $this->cloudinary->destroy($medium->public_id);
            } catch (\Throwable $e) {
                // Swallow remote failure — local row deletion still proceeds.
            }
        }

        $medium->delete();

        return response()->json(['ok' => true]);
    }
}
