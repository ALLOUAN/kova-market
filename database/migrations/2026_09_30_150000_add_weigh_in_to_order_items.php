<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Weigh-in at preparation (App\Services\Orders\WeighIn): a line sold by weight or volume may be set to the quantity
 * really weighed; the quantity the customer ordered is kept beside it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedInteger('ordered_quantity')->nullable()->after('quantity');
            $table->timestamp('weighed_at')->nullable()->after('unit_label');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['ordered_quantity', 'weighed_at']);
        });
    }
};
