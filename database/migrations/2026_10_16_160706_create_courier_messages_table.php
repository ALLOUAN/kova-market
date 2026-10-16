<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Direct chat between each courier and the back-office: one conversation per courier, each message read
     * ("read_at") by the other side.
     */
    public function up(): void
    {
        Schema::create('courier_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('from_staff')->default(false);
            $table->text('body');
            $table->dateTime('read_at')->nullable();
            $table->timestamps();

            $table->index(['courier_id', 'id']);
            $table->index(['from_staff', 'read_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courier_messages');
    }
};
