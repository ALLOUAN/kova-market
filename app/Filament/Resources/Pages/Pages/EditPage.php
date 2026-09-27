<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use Filament\Resources\Pages\EditRecord;

/**
 * No delete action: menus and footer link to these pages. Unpublish a page instead.
 */
class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;
}
