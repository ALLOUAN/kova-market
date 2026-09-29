<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Online payment attempts of an order (F-060 to F-067): one row per redirection to CinetPay, with what CinetPay
     * answered at each step (F-066 journal). The order keeps its own payment status.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->string('provider', 20)->default('cinetpay');
            // Our reference sent to CinetPay (30 characters at most), and theirs.
            $table->string('merchant_transaction_id', 30)->unique();
            $table->string('gateway_transaction_id')->nullable()->index();
            $table->string('payment_token')->nullable();
            // SHA-256 of the notify_token CinetPay returns at initiation: a notification must carry the same token.
            $table->string('notify_token_hash', 64)->nullable();
            $table->string('payment_url', 500)->nullable();
            $table->unsignedInteger('amount');
            $table->string('currency', 3);
            $table->string('status', 20)->index();
            $table->string('operator')->nullable();
            $table->string('payer_phone', 20)->nullable();
            $table->string('failure_reason')->nullable();
            $table->json('events')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->foreignId('refunded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('refund_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
