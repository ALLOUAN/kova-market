<?php

namespace App\Support;

use App\Models\Concerns\RedirectsOldSlugs;
use App\Models\SlugRedirect;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Answers a storefront page asked with a former slug (F-150) with a 301 to the same page under the current slug,
 * query string kept. Anything else stays a 404.
 */
class SlugRedirector
{
    public static function respond(Request $request, ModelNotFoundException $exception): ?RedirectResponse
    {
        $class = $exception->getModel();
        $route = $request->route();

        if (! $request->isMethod('GET') || ! $route?->getName() || ! in_array(RedirectsOldSlugs::class, class_uses_recursive($class), true)) {
            return null;
        }

        $model = new $class;
        $oldSlug = (string) collect($exception->getIds())->first();
        $target = SlugRedirect::where('redirectable_type', $model->getMorphClass())->where('old_slug', $oldSlug)->first()?->redirectable;

        if (! $target) {
            return null;
        }

        // The route parameter that held the old slug now takes the record's current slug.
        $parameters = collect($route->parameters())->map(fn ($value) => $value === $oldSlug ? $target->slug : $value)->all();
        $query = $request->getQueryString();

        return redirect()->to(route($route->getName(), $parameters).($query ? "?{$query}" : ''), 301);
    }
}
