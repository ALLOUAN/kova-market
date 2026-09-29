<?php

namespace App\Filament\Resources\Testimonials;

use App\Enums\Permission;
use App\Filament\Concerns\AuthorizesWithPermission;
use App\Filament\Resources\Testimonials\Pages\CreateTestimonial;
use App\Filament\Resources\Testimonials\Pages\EditTestimonial;
use App\Filament\Resources\Testimonials\Pages\ListTestimonials;
use App\Filament\Resources\Testimonials\Schemas\TestimonialForm;
use App\Filament\Resources\Testimonials\Tables\TestimonialsTable;
use App\Models\Testimonial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Customer testimonials of the sign-in and sign-up windows.
 */
class TestimonialResource extends Resource
{
    use AuthorizesWithPermission;

    protected static ?string $model = Testimonial::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Contenus';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'témoignage';

    protected static ?string $pluralModelLabel = 'témoignages clients';

    protected static ?string $recordTitleAttribute = 'author_name';

    protected static function managePermission(): Permission
    {
        return Permission::ManageContent;
    }

    public static function form(Schema $schema): Schema
    {
        return TestimonialForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TestimonialsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTestimonials::route('/'),
            'create' => CreateTestimonial::route('/create'),
            'edit' => EditTestimonial::route('/{record}/edit'),
        ];
    }
}
