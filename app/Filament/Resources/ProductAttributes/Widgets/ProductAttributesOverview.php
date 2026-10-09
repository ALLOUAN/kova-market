<?php

namespace App\Filament\Resources\ProductAttributes\Widgets;

use App\Filament\Resources\ProductAttributes\ProductAttributeResource;
use App\Filament\Support\ListHeroWidget;
use App\Models\AttributeValue;
use App\Models\ProductAttribute;
use App\Models\ProductVariant;

/**
 * Header of the attributes list (Couleur, Taille, Capacité…): how many, their values, the variants built on them and
 * the attributes nobody uses yet.
 */
class ProductAttributesOverview extends ListHeroWidget
{
    protected function hero(): array
    {
        $total = ProductAttribute::query()->count();
        $values = AttributeValue::query()->count();
        $used = ProductAttribute::query()->whereHas('values.variants')->count();
        $variants = ProductVariant::query()->has('attributeValues')->count();
        $tab = fn (string $tab) => ProductAttributeResource::getUrl('index', ['tab' => $tab]);

        return [
            'title' => 'Attributs',
            'icon' => 'heroicon-o-adjustments-horizontal',
            'lead' => 'Les caractéristiques qui distinguent les variantes d’un produit (couleur, taille, capacité…) : <strong>'.$total.'</strong> attribut'.($total > 1 ? 's' : '').', <strong>'.$values.'</strong> valeur'.($values > 1 ? 's' : '').'.',
            'kpis' => [
                self::kpi('Attributs', (string) $total, $total > 0 ? 'Environ '.number_format($values / $total, 1, ',', '').' valeurs chacun' : 'Aucun attribut',
                    'heroicon-o-adjustments-horizontal', 'navy', $tab('all')),
                self::kpi('Valeurs', (string) $values, 'Choix proposés aux clients',
                    'heroicon-o-swatch', 'orange'),
                self::kpi('Variantes', (string) $variants, 'Construites sur ces attributs',
                    'heroicon-o-squares-plus', 'green', $tab('used')),
                self::kpi('Inutilisés', (string) ($total - $used), $total - $used > 0 ? 'Aucune variante ne s’en sert' : 'Tous servent à des variantes',
                    'heroicon-o-inbox', 'gold', $tab('unused'), $total - $used > 0 ? 'gold' : null),
            ],
        ];
    }
}
