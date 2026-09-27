<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Validation\ValidationException;

/**
 * One value of an attribute, e.g. "Noir" for "Couleur" (with its swatch colour) or "XL" for "Taille".
 */
#[Fillable(['attribute_id', 'value', 'color_hex', 'position'])]
class AttributeValue extends Model
{
    protected static function booted(): void
    {
        // A value worn by a variant cannot disappear: say so instead of hitting the database constraint.
        static::deleting(function (AttributeValue $value): void {
            if ($value->variants()->exists()) {
                throw ValidationException::withMessages([
                    'data.values' => "La valeur « {$value->value} » est utilisée par des variantes : elle ne peut pas être supprimée.",
                ]);
            }
        });
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'attribute_id');
    }

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class);
    }

    /**
     * "Couleur : Noir".
     */
    public function label(): string
    {
        return "{$this->attribute->name} : {$this->value}";
    }
}
