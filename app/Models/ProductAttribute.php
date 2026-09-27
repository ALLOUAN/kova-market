<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Variant attribute such as "Couleur" or "Taille" (F-035).
 */
#[Fillable(['name', 'slug', 'position'])]
class ProductAttribute extends Model
{
    protected $table = 'attributes';

    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class, 'attribute_id')->orderBy('position')->orderBy('id');
    }
}
