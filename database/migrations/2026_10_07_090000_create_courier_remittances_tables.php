<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Couriers' remittances (F-126): the cash collected on delivery is handed over in one or several payments.
     *  - courier_remittances: each payment, never deleted (a mistake is cancelled with its reason);
     *  - courier_remittance_order: how much of a payment covers each order, oldest order first;
     *  - orders.cash_remitted: the part of the cash already handed over (cash_settled_at once it is all of it);
     *  - orders.cash_note: why the courier collected another amount than the one due.
     * Orders settled before this table existed get one remittance per hand-over, so the history stays complete.
     */
    public function up(): void
    {
        Schema::create('courier_remittances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courier_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');
            // What the courier still owed right after this payment (unknown for hand-overs taken over below).
            $table->unsignedInteger('balance_after')->nullable();
            $table->string('method', 20);
            $table->string('reference', 100)->nullable();
            $table->dateTime('received_at');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 255)->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancel_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['courier_id', 'received_at']);
        });

        Schema::create('courier_remittance_order', function (Blueprint $table) {
            $table->foreignId('courier_remittance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');

            $table->primary(['courier_remittance_id', 'order_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('cash_remitted')->default(0)->after('cash_collected');
            $table->string('cash_note', 255)->nullable()->after('cash_remitted');
        });

        // Hand-overs recorded before: one remittance per courier, day and person who received the money.
        $settled = DB::table('orders')
            ->whereNotNull('courier_id')->whereNotNull('cash_collected')->whereNotNull('cash_settled_at')
            ->orderBy('cash_settled_at')
            ->get(['id', 'courier_id', 'cash_collected', 'cash_settled_at', 'cash_settled_by']);

        foreach ($settled->groupBy(fn ($order) => $order->courier_id.'|'.$order->cash_settled_at.'|'.$order->cash_settled_by) as $orders) {
            $first = $orders->first();
            $remittance = DB::table('courier_remittances')->insertGetId([
                'courier_id' => $first->courier_id,
                'amount' => $orders->sum('cash_collected'),
                'method' => 'especes',
                'received_at' => $first->cash_settled_at,
                'received_by' => $first->cash_settled_by,
                'note' => 'Reprise des encaissements reçus avant les versements partiels',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($orders as $order) {
                DB::table('courier_remittance_order')->insert(['courier_remittance_id' => $remittance, 'order_id' => $order->id, 'amount' => $order->cash_collected]);
                DB::table('orders')->where('id', $order->id)->update(['cash_remitted' => $order->cash_collected]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['cash_remitted', 'cash_note']));
        Schema::dropIfExists('courier_remittance_order');
        Schema::dropIfExists('courier_remittances');
    }
};
