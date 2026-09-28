<?php

namespace App\Models\Concerns;

use App\Models\SlugRedirect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Remembers every former slug of the model (F-150), so that its old address redirects to the current one with a 301
 * (App\Support\SlugRedirector). A slug taken again by a record drops the redirect that used it.
 *
 * @mixin Model
 */
trait RedirectsOldSlugs
{
    public static function bootRedirectsOldSlugs(): void
    {
        static::saved(function (Model $model): void {
            SlugRedirect::where('redirectable_type', $model->getMorphClass())->where('old_slug', $model->slug)->delete();

            if ($model->wasChanged('slug') && filled($old = $model->getOriginal('slug'))) {
                SlugRedirect::updateOrCreate(
                    ['redirectable_type' => $model->getMorphClass(), 'old_slug' => $old],
                    ['redirectable_id' => $model->getKey()],
                );
            }
        });

        static::deleted(fn (Model $model) => $model->slugRedirects()->delete());
    }

    public function slugRedirects(): MorphMany
    {
        return $this->morphMany(SlugRedirect::class, 'redirectable');
    }
}
