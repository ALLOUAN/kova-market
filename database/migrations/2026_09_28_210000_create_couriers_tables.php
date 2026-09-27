<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Couriers (F-122 to F-127): accounts created by the back-office (a user with the "livreur" role and its
     * profile), their zones, the order each one delivers, and the cash collected on delivery until it is
     * handed over to the store.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dateTime('suspended_at')->nullable()->after('marketing_opt_in');
            $table->boolean('must_change_password')->default(false)->after('suspended_at');
        });

        Schema::create('couriers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('transport', 20);
            $table->string('photo')->nullable();
            $table->timestamps();
        });

        Schema::create('courier_delivery_zone', function (Blueprint $table) {
            $table->foreignId('courier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_zone_id')->constrained()->cascadeOnDelete();
            $table->primary(['courier_id', 'delivery_zone_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('courier_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->dateTime('assigned_at')->nullable()->after('courier_id');
            $table->date('delivery_date')->nullable()->after('assigned_at');
            $table->unsignedInteger('cash_collected')->nullable()->after('total');
            $table->dateTime('cash_settled_at')->nullable()->after('cash_collected');
            $table->foreignId('cash_settled_by')->nullable()->after('cash_settled_at')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cash_settled_by');
            $table->dropConstrainedForeignId('courier_id');
            $table->dropColumn(['assigned_at', 'delivery_date', 'cash_collected', 'cash_settled_at']);
        });

        Schema::dropIfExists('courier_delivery_zone');
        Schema::dropIfExists('couriers');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['suspended_at', 'must_change_password']);
        });
    }
};
