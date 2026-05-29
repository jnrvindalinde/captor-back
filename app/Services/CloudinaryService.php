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

        $response = $uploader->upload($file->getRealPath(), [
            'folder'        => $folder,
            'resource_type' => 'auto',
            'use_filename'  => true,
            'unique_filename' => true,
            'overwrite'     => false,
        ]);

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
