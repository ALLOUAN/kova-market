<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Who looked at a product page lately: one row per product and visitor (a hash of the session, no personal
        // data), kept for a few minutes only. Feeds the "N personnes ont vu ce produit" badge.
        Schema::create('product_viewers', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->char('visitor', 64);
            $table->timestamp('seen_at')->index();

            $table->primary(['product_id', 'visitor']);
        });

        // The template's sample figures ("16 personnes regardent ce produit") were not measured: from now on the
        // badge only shows real visits.
        DB::table('products')->update(['watchers_count' => null]);
    }

    public function down(): void
    {
        Schema::dropIfExists('product_viewers');
    }
};
