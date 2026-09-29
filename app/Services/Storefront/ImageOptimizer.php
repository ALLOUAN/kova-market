<?php

namespace App\Services\Storefront;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Images uploaded from the back-office (performance, fiche §9): stored as WebP at most MAX_WIDTH pixels wide, with a
 * SMALL_WIDTH copy ("<name>-480.webp") that product cards and lists load through srcset. A phone photo of several
 * megabytes becomes a file of a few hundred kilobytes, and a card downloads a fraction of it.
 */
class ImageOptimizer
{
    public const MAX_WIDTH = 1600;

    public const SMALL_WIDTH = 480;

    private const QUALITY = 82;

    /**
     * Stores the upload on the storefront disk under $directory and returns its path. A file GD cannot read is kept
     * as uploaded (the form already refuses anything but JPG, PNG and WebP).
     */
    public function store(UploadedFile $file, string $directory, string $disk = 'storefront'): string
    {
        $image = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if (! $image instanceof GdImage) {
            return $file->storeAs($directory, Str::ulid().'.'.$file->getClientOriginalExtension(), $disk);
        }

        $name = $directory.'/'.Str::ulid();
        Storage::disk($disk)->put("{$name}.webp", $this->webp($image, self::MAX_WIDTH));

        if (imagesx($image) > self::SMALL_WIDTH) {
            Storage::disk($disk)->put("{$name}-".self::SMALL_WIDTH.'.webp', $this->webp($image, self::SMALL_WIDTH));
        }

        imagedestroy($image);

        return "{$name}.webp";
    }

    /**
     * The path of the small copy of an image, when it exists.
     */
    public static function smallVariant(?string $path): ?string
    {
        if (blank($path) || ! str_ends_with($path, '.webp')) {
            return null;
        }

        $variant = substr($path, 0, -5).'-'.self::SMALL_WIDTH.'.webp';

        return is_file(public_path($variant)) ? $variant : null;
    }

    /**
     * srcset for an image with a small copy ("… 480w, … 1600w"), or null to keep the plain src.
     */
    public static function srcset(?string $path): ?string
    {
        $variant = self::smallVariant($path);

        if (! $variant) {
            return null;
        }

        $width = @getimagesize(public_path($path))[0] ?? self::MAX_WIDTH;

        return asset($variant).' '.self::SMALL_WIDTH.'w, '.asset($path).' '.$width.'w';
    }

    private function webp(GdImage $image, int $maxWidth): string
    {
        $copy = imagesx($image) > $maxWidth ? imagescale($image, $maxWidth, -1, IMG_BICUBIC) : $image;

        // Transparent PNG / WebP keep their transparency.
        imagepalettetotruecolor($copy);
        imagealphablending($copy, false);
        imagesavealpha($copy, true);

        ob_start();
        imagewebp($copy, null, self::QUALITY);
        $content = (string) ob_get_clean();

        if ($copy !== $image) {
            imagedestroy($copy);
        }

        return $content;
    }
}
