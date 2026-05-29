<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Api\Upload\UploadApi;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class CloudinaryService
{
    private ?Cloudinary $client = null;

    public function __construct()
    {
        $url = config('services.cloudinary.url');
        if ($url) {
            $this->client = new Cloudinary($url);
            return;
        }

        $cloud = config('services.cloudinary.cloud_name');
        $key   = config('services.cloudinary.api_key');
        $sec   = config('services.cloudinary.api_secret');

        if ($cloud && $key && $sec) {
            $this->client = new Cloudinary([
                'cloud' => [
                    'cloud_name' => $cloud,
                    'api_key'    => $key,
                    'api_secret' => $sec,
                ],
                'url' => ['secure' => true],
            ]);
        }
    }

    public function isConfigured(): bool
    {
        return $this->client !== null;
    }

    /**
     * Upload a file to Cloudinary. Returns the raw API response array.
     *
     * @return array<string, mixed>
     */
    public function upload(UploadedFile $file, ?string $folder = null): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Cloudinary is not configured. Set CLOUDINARY_URL or CLOUDINARY_* env vars.');
        }

        $folder = $folder ?: config('services.cloudinary.folder', 'captor');

        /** @var UploadApi $uploader */
        $uploader = $this->client->uploadApi();

        $path = $file->getRealPath();
        $size = $file->getSize();

        // Pick the right Cloudinary resource_type by MIME. Auto-detection routes
        // PDFs/docs to "image", whose delivery endpoint blocks them by default
        // (401). Forcing "raw" for non-AV files makes them served via
        // /raw/upload/ which has no PDF/ZIP delivery restriction.
        $mime = (string) $file->getMimeType();
        if (str_starts_with($mime, 'image/')) {
            $resourceType = 'image';
        } elseif (str_starts_with($mime, 'video/')) {
            $resourceType = 'video';
        } elseif (str_starts_with($mime, 'audio/')) {
            // Cloudinary serves audio assets under the "video" resource_type.
            $resourceType = 'video';
        } else {
            $resourceType = 'raw';
        }

        $opts = [
            'folder'            => $folder,
            'resource_type'     => $resourceType,
            'use_filename'      => true,
            'unique_filename'   => true,
            'overwrite'         => false,
            // PHP's UploadedFile points at a tmp path like "php4149_mcieht";
            // pass the real client-supplied filename so Cloudinary builds a
            // recognisable public_id / URL ("guide.pdf" not "php4149_mcieht").
            'filename_override' => $file->getClientOriginalName(),
        ];

        // Cloudinary's single-upload endpoint caps at 100 MB. Anything larger
        // must go through the chunked uploadLarge endpoint (supports up to 6 GB
        // with 20 MB chunks). We branch only when needed so small uploads keep
        // the simpler/faster path and resource_type=auto detection.
        $threshold = 95 * 1024 * 1024; // 95 MB safety margin
        if ($size !== false && $size > $threshold) {
            $opts['chunk_size'] = 20 * 1024 * 1024; // 20 MB per chunk
            $response = $uploader->uploadLarge($path, $opts);
        } else {
            $response = $uploader->upload($path, $opts);
        }

        return (array) $response;
    }

    public function destroy(string $publicId): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $this->client->uploadApi()->destroy($publicId);
    }
}
