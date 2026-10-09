<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Conditions of a delivery zone, all optional:
     *  - min_order: smallest amount of goods accepted for the zone;
     *  - free_shipping_threshold: free delivery from this amount of goods (empty: the store's general threshold);
     *  - delivery_days: weekdays the zone is delivered (ISO 1 = Monday ... 7 = Sunday; empty: every day).
     */
    public function up(): void
    {
        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->unsignedInteger('min_order')->nullable()->after('fee');
            $table->unsignedInteger('free_shipping_threshold')->nullable()->after('min_order');
            $table->json('delivery_days')->nullable()->after('delay_label');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_zones', fn (Blueprint $table) => $table->dropColumn(['min_order', 'free_shipping_threshold', 'delivery_days']));
    }
};
