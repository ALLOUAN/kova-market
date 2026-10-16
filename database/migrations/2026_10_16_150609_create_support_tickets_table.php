<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Assistance requests from the customer area: a conversation between the customer and the team, about an order
     * or anything else, open until one of them closes it.
     */
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject', 40);
            $table->string('title', 120);
            $table->string('status', 20)->default('ouvert');
            $table->boolean('unread_by_customer')->default(false);
            $table->dateTime('last_message_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'last_message_at']);
        });

        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('from_staff')->default(false);
            $table->text('body');
            // Files joined to the message (photos, receipts), kept on the private disk: [{path, name, size}].
            $table->json('attachments')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
    }
};
