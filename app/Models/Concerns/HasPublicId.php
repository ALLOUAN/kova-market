<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * A random public id (ULID, "public_id") used in the site's addresses instead of the database number, which stays the
 * primary key: /compte/adresses/01k7… cannot be guessed from another record's address. Filled on creation; a
 * malformed id in an address answers 404.
 *
 * @mixin Model
 */
trait HasPublicId
{
    use HasUlids;

    /**
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
