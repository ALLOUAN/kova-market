<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Promo codes (F-091, F-042): one code per cart and per order, every use recorded in `coupon_usages`.
     * `times_used` is the counter checked under a row lock for the global cap.
     */
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('description')->nullable();
            $table->string('type', 20);
            $table->unsignedInteger('value')->default(0);
            $table->unsignedInteger('minimum_subtotal')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_limit_per_customer')->nullable();
            $table->unsignedInteger('times_used')->default(0);
            $table->string('target', 20)->default('tout');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });

        Schema::create('category_coupon', function (Blueprint $table) {
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->primary(['coupon_id', 'category_id']);
        });

        Schema::create('coupon_product', function (Blueprint $table) {
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['coupon_id', 'product_id']);
        });

        // A used code cannot be deleted: it can only be switched off.
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone', 20);
            $table->unsignedInteger('amount');
            $table->timestamps();

            $table->index(['coupon_id', 'phone']);
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->foreignId('coupon_id')->nullable()->after('commune_id')->constrained()->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('coupon_code', 40)->nullable()->after('discount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('coupon_code');
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coupon_id');
        });

        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupon_product');
        Schema::dropIfExists('category_coupon');
        Schema::dropIfExists('coupons');
    }
};
