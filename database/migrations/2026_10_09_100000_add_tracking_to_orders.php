<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What Meta's Conversions API needs to match a sale confirmed later by CinetPay's server (browser, IP, Meta
     * cookies), kept only when the customer accepted the cookies.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->json('tracking')->nullable()->after('terms_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('tracking');
        });
    }
};
