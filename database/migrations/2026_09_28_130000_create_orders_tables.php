<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Orders (F-050 to F-056): customer and delivery details and amounts are copied at order time, so
     * later changes to products, zones or the account never rewrite an order.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->index();
            $table->string('payment_method', 30);
            $table->string('payment_status', 20)->index();
            $table->string('source', 10)->default('web');

            $table->string('customer_name');
            $table->string('phone', 20)->index();
            $table->string('email')->nullable();
            $table->foreignId('commune_id')->nullable()->constrained()->nullOnDelete();
            $table->string('commune_name');
            $table->string('zone_name');
            $table->string('district');
            $table->string('landmark')->nullable();
            $table->text('note')->nullable();

            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('shipping_fee');
            $table->unsignedInteger('discount')->default(0);
            $table->unsignedInteger('total');

            $table->boolean('marketing_opt_in')->default(false);
            $table->dateTime('terms_accepted_at');
            $table->softDeletes();
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('variant_label')->nullable();
            $table->string('sku', 64);
            $table->string('image')->nullable();
            $table->unsignedInteger('unit_price');
            $table->unsignedSmallInteger('quantity');
            $table->unsignedInteger('line_total');
            $table->timestamps();
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        // One row per day: the daily counter behind "KM-AAMMJJ-XXXX", incremented under a row lock.
        Schema::create('order_number_sequences', function (Blueprint $table) {
            $table->date('day')->primary();
            $table->unsignedInteger('last_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_number_sequences');
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
