<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The "Nouveautés" and "Populaires" rows of the home page (F-011, F-012) are computed from the catalog; these
     * empty collections let the back-office curate them instead (products added there take precedence).
     */
    public function up(): void
    {
        DB::table('collections')->insertOrIgnore([
            ['name' => 'Nouveautés', 'slug' => 'new-arrivals', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Populaires', 'slug' => 'popular-products', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        DB::table('collections')->whereIn('slug', ['new-arrivals', 'popular-products'])->delete();
    }
};
