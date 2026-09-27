<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Customer address book (F-071) and marketing consent kept on the account (F-053, F-074).
     */
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 50);
            $table->string('recipient_name');
            $table->string('phone', 20);
            $table->foreignId('commune_id')->nullable()->constrained()->nullOnDelete();
            $table->string('district');
            $table->string('landmark')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_default']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('marketing_opt_in')->default(false)->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('marketing_opt_in');
        });

        Schema::dropIfExists('addresses');
    }
};
