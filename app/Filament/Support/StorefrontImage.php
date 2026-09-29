<?php

namespace App\Filament\Support;

use App\Services\Storefront\ImageOptimizer;
use Filament\Forms\Components\FileUpload;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Image upload stored on the "storefront" disk (public/uploads/...), so the saved path renders with asset()
 * exactly like the template images already in the database. Saved as optimised WebP with a small copy for the
 * product cards (ImageOptimizer).
 */
class StorefrontImage
{
    public static function make(string $field, string $directory): FileUpload
    {
        return FileUpload::make($field)
            ->disk('storefront')
            ->directory("uploads/{$directory}")
            ->visibility('public')
            ->image()
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->maxSize(5120)
            ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file): string => app(ImageOptimizer::class)->store($file, "uploads/{$directory}"))
            ->helperText('JPG, PNG ou WebP, 5 Mo maximum. L’image est allégée automatiquement (WebP, 1600 px de large au plus).');
    }
}
