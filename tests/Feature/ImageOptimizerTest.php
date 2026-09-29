<?php

namespace Tests\Feature;

use App\Services\Storefront\ImageOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageOptimizerTest extends TestCase
{
    public function test_an_uploaded_photo_is_stored_as_a_lighter_webp_with_a_small_copy(): void
    {
        Storage::fake('storefront');
        $photo = UploadedFile::fake()->image('photo.jpg', 3000, 2000);

        $path = app(ImageOptimizer::class)->store($photo, 'uploads/products');

        $this->assertMatchesRegularExpression('#^uploads/products/[0-9A-Z]{26}\.webp$#', $path);
        [$width, $height, $type] = getimagesizefromstring(Storage::disk('storefront')->get($path));
        $this->assertSame([1600, IMAGETYPE_WEBP], [$width, $type]);
        $this->assertEqualsWithDelta(1067, $height, 1, 'The proportions are kept.');

        $small = substr($path, 0, -5).'-480.webp';
        $this->assertSame(480, getimagesizefromstring(Storage::disk('storefront')->get($small))[0]);
    }

    public function test_a_small_picture_keeps_its_size_and_gets_no_copy(): void
    {
        Storage::fake('storefront');

        $path = app(ImageOptimizer::class)->store(UploadedFile::fake()->image('logo.png', 300, 120), 'uploads/brands');

        $this->assertSame(300, getimagesizefromstring(Storage::disk('storefront')->get($path))[0]);
        $this->assertCount(1, Storage::disk('storefront')->files('uploads/brands'));
    }

    public function test_cards_get_a_srcset_only_for_images_with_a_small_copy(): void
    {
        $directory = public_path('uploads/test-srcset');
        File::ensureDirectoryExists($directory);
        imagewebp(imagecreatetruecolor(1200, 800), "{$directory}/photo.webp");
        imagewebp(imagecreatetruecolor(480, 320), "{$directory}/photo-480.webp");

        try {
            $this->assertSame(
                asset('uploads/test-srcset/photo-480.webp').' 480w, '.asset('uploads/test-srcset/photo.webp').' 1200w',
                ImageOptimizer::srcset('uploads/test-srcset/photo.webp'),
            );
            $this->assertNull(ImageOptimizer::srcset('assets/images/products/theme-picture.webp'));
            $this->assertNull(ImageOptimizer::srcset(null));
        } finally {
            File::deleteDirectory($directory);
        }
    }
}
