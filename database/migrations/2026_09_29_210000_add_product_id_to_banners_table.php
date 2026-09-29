<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A banner can promote one product: its price, crossed-out price, discount and link then come from it and
        // follow its changes.
        Schema::table('banners', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('placement')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
        });
    }
};
