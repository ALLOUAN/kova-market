<?php

namespace App\Filament\Support;

use Filament\Forms\Components\FileUpload;

/**
 * Image upload stored on the "storefront" disk (public/uploads/...), so the saved path renders with asset()
 * exactly like the template images already in the database.
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
            ->maxSize(2048)
            ->helperText('JPG, PNG ou WebP, 2 Mo maximum.');
    }
}
