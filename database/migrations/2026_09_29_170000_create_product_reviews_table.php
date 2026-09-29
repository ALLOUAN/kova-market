<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Product reviews: one per delivered order line, published once the back-office approves it.
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('author_name', 80);
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->string('status', 20)->default('en_attente')->index();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'status']);
        });

        // The template's sample ratings ("5 stars (46)") were not real reviews: from now on the rating and the count
        // come from approved reviews only.
        DB::table('products')->update(['rating' => 0, 'reviews_count' => 0]);
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
