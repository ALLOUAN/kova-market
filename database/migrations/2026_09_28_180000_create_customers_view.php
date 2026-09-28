<?php

use App\Support\Database\CustomersView;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Customers of the back-office (F-108), as a read-only view (see CustomersView).
     */
    public function up(): void
    {
        CustomersView::create();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        CustomersView::drop();
    }
};
