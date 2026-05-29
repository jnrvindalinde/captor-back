<?php

namespace Database\Factories;

use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        $w = $this->faker->numberBetween(800, 2400);
        $h = $this->faker->numberBetween(600, 1600);
        $publicId = 'captor/'.Str::slug($this->faker->words(2, true)).'-'.Str::lower(Str::random(8));

        return [
            'provider'          => 'cloudinary',
            'public_id'         => $publicId,
            'secure_url'        => "https://res.cloudinary.com/demo/image/upload/{$publicId}.jpg",
            'format'            => 'jpg',
            'width'             => $w,
            'height'            => $h,
            'bytes'             => $this->faker->numberBetween(50_000, 800_000),
            'original_filename' => $this->faker->word().'.jpg',
            'folder'            => 'captor',
            'alt_en'            => $this->faker->sentence(4),
            'alt_fr'            => null,
            'caption_en'        => null,
            'caption_fr'        => null,
            'meta'              => [],
            'uploaded_by'       => null,
        ];
    }
}
