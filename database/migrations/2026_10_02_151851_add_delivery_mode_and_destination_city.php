<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Abidjan delivery or shipping to the interior of the country:
     *  - a zone says how its destinations are reached (delivery_mode);
     *  - the "Intérieur du pays" zone gets its single destination, "Intérieur", the customer then types the town;
     *  - orders keep their delivery mode and destination town, addresses their town.
     */
    public function up(): void
    {
        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->string('delivery_mode')->default('abidjan')->after('delay_label');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('delivery_mode')->default('abidjan')->after('zone_name');
            $table->string('destination_city')->nullable()->after('delivery_mode');
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->string('city')->nullable()->after('commune_id');
        });

        // Existing installations: the zone created by DeliverySeeder (fresh ones get it from the seeder).
        $interior = DB::table('delivery_zones')->where('name', 'Intérieur du pays')->value('id');

        if ($interior) {
            DB::table('delivery_zones')->where('id', $interior)->update(['delivery_mode' => 'interieur']);

            if (! DB::table('communes')->where('name', 'Intérieur')->exists()) {
                DB::table('communes')->insert([
                    'delivery_zone_id' => $interior, 'name' => 'Intérieur', 'position' => 0, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations. The "Intérieur" destination is removed only when no order or address uses it.
     */
    public function down(): void
    {
        $commune = DB::table('communes')->where('name', 'Intérieur')->value('id');

        if ($commune && ! DB::table('orders')->where('commune_id', $commune)->exists() && ! DB::table('addresses')->where('commune_id', $commune)->exists()) {
            DB::table('communes')->where('id', $commune)->delete();
        }

        Schema::table('addresses', fn (Blueprint $table) => $table->dropColumn('city'));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['delivery_mode', 'destination_city']));
        Schema::table('delivery_zones', fn (Blueprint $table) => $table->dropColumn('delivery_mode'));
    }
};
