<?php

use App\Support\Database\CustomersView;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Accounts removed from the back-office are kept and can be restored (F-145): no physical deletion.
     */
    public function up(): void
    {
        CustomersView::drop();

        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        CustomersView::create();
    }

    public function down(): void
    {
        CustomersView::drop();

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        CustomersView::create();
    }
};
