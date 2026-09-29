<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "Offres spéciales" panel: a campaign can be hidden without being deleted, and put in the chosen order.
        Schema::table('promotions', function (Blueprint $table) {
            $table->boolean('is_visible')->default(true)->after('url');
            $table->unsignedInteger('position')->default(0)->after('is_visible');
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn(['is_visible', 'position']);
        });
    }
};
