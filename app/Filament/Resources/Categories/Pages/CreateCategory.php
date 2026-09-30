<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use Filament\Resources\Pages\CreateRecord;
use Livewire\Attributes\Url;

class CreateCategory extends CreateRecord
{
    /** Query value of "Ajouter une sous-catégorie": the form asks for the parent category. */
    public const SUB_CATEGORY = 'sous-categorie';

    /** Query value of "Ajouter une catégorie parente": a main department, no parent to choose. */
    public const PARENT_CATEGORY = 'parente';

    protected static string $resource = CategoryResource::class;

    #[Url]
    public ?string $type = null;

    public function mount(): void
    {
        // "+" on a row of the list gives the parent: that is a sub-category too.
        if (request()->integer('parent')) {
            $this->type = self::SUB_CATEGORY;
        }

        parent::mount();
    }

    public function isSubCategory(): bool
    {
        return $this->type === self::SUB_CATEGORY;
    }

    public function isParentCategory(): bool
    {
        return $this->type === self::PARENT_CATEGORY;
    }

    public function getTitle(): string
    {
        return match (true) {
            $this->isSubCategory() => 'Ajouter une sous-catégorie',
            $this->isParentCategory() => 'Ajouter une catégorie parente',
            default => parent::getTitle(),
        };
    }

    public function getSubheading(): ?string
    {
        return match (true) {
            $this->isSubCategory() => 'Choisissez la catégorie dans laquelle la ranger. Elle apparaîtra sous celle-ci dans les menus de la boutique.',
            $this->isParentCategory() => 'Un rayon principal de la boutique : il apparaît dans le menu « Boutique », le menu mobile et le panneau des catégories. Vous y rangerez ensuite ses sous-catégories.',
            default => null,
        };
    }

    public function getBreadcrumbs(): array
    {
        return match (true) {
            $this->isSubCategory() => [CategoryResource::getUrl('sub') => 'Sous-catégories', 'Ajouter'],
            $this->isParentCategory() => [CategoryResource::getUrl('parents') => 'Catégories parentes', 'Ajouter'],
            default => parent::getBreadcrumbs(),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($this->isParentCategory()) {
            $data['parent_id'] = null;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        // Back to the list the button was on, where the new category shows.
        return match (true) {
            $this->isSubCategory() => CategoryResource::getUrl('sub'),
            $this->isParentCategory() => CategoryResource::getUrl('parents'),
            default => parent::getRedirectUrl(),
        };
    }
}
