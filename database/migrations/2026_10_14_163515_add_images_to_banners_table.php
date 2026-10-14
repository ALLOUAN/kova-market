<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Further images of a banner, shown in turn after its main image (every home page placement but the main carousel).
     */
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->json('images')->nullable()->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('images');
        });
    }
};
