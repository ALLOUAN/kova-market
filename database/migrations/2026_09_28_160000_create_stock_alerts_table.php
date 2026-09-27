<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Back-in-stock alerts (EX-17): a visitor leaves a phone number or an e-mail on a sold-out product, or on one
     * sold-out variant, and is told once when it is back.
     */
    public function up(): void
    {
        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->dateTime('notified_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'notified_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_alerts');
    }
};
