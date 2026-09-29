<?php

namespace App\Filament\Support;

use App\Services\Storefront\ImageOptimizer;
use Closure;
use Filament\Forms\Components\FileUpload;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Image upload stored on the "storefront" disk (public/uploads/...), so the saved path renders with asset()
 * exactly like the template images already in the database. Saved as optimised WebP with a small copy for the
 * product cards (ImageOptimizer).
 *
 * Each field states the dimensions the image has on the site (those of the template's own images), and shows the
 * dimensions of the image in place, so the back-office can prepare images that fit.
 */
class StorefrontImage
{
    /**
     * @param  array{0: int, 1: int}|Closure|null  $size  width and height on the site, in pixels (a closure may
     *                                                    depend on the form, e.g. the banner's placement)
     * @param  string|Closure|null  $note  how the image is shown (transparent background, square...)
     */
    public static function make(string $field, string $directory, array|Closure|null $size = null, string|Closure|null $note = null): FileUpload
    {
        return FileUpload::make($field)
            ->disk('storefront')
            ->directory("uploads/{$directory}")
            ->visibility('public')
            ->image()
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->maxSize(5120)
            ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file): string => app(ImageOptimizer::class)->store($file, "uploads/{$directory}"))
            ->helperText(fn (FileUpload $component): string => self::help($component->evaluate($size), $component->evaluate($note), self::currentSize($component->getState())));
    }

    /**
     * @param  array{0: int, 1: int}|null  $size
     */
    public static function help(?array $size, ?string $note = null, ?string $current = null): string
    {
        $dimensions = $size ? "Dimensions sur le site : {$size[0]} × {$size[1]} px".($note ? " ({$note})" : '').'. ' : ($note ? ucfirst($note).'. ' : '');

        return $dimensions.($current ? "{$current}. " : '').'JPG, PNG ou WebP, 5 Mo maximum. L’image est allégée automatiquement (WebP, 1600 px de large au plus).';
    }

    /**
     * "Image actuelle : 1296 × 908 px", for the image saved or just chosen.
     */
    private static function currentSize(mixed $state): ?string
    {
        $file = collect(is_array($state) ? $state : [$state])->filter()->first();

        $path = match (true) {
            $file instanceof TemporaryUploadedFile => $file->getRealPath(),
            is_string($file) => public_path($file),
            default => null,
        };

        $size = $path && is_file($path) ? @getimagesize($path) : false;

        return $size ? "Image actuelle : {$size[0]} × {$size[1]} px" : null;
    }
}
