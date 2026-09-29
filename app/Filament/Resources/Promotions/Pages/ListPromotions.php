<?php

namespace App\Filament\Resources\Promotions\Pages;

use App\Filament\Resources\Promotions\PromotionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPromotions extends ListRecords
{
    protected static string $resource = PromotionResource::class;

    protected static ?string $title = 'Offres spéciales';

    public function getSubheading(): ?string
    {
        return 'Les cartes du panneau « Offres spéciales » de la boutique (lien en haut à droite du menu) : image, dates, portée, titre, texte et lien. Chaque carte disparaît après sa date de fin ; décochez « Visible » pour la masquer avant.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
