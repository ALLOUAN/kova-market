<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A home selection can be prepared in advance and shows from this date ("Black Friday").
        Schema::table('collections', function (Blueprint $table) {
            $table->timestamp('starts_at')->nullable()->after('slug');
        });

        // The text of a banner's button ("Acheter maintenant" when empty).
        Schema::table('banners', function (Blueprint $table) {
            $table->string('button_label', 40)->nullable()->after('url');
        });
    }

    public function down(): void
    {
        Schema::table('collections', fn (Blueprint $table) => $table->dropColumn('starts_at'));
        Schema::table('banners', fn (Blueprint $table) => $table->dropColumn('button_label'));
    }
};
