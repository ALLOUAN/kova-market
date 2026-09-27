<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Home page banners managed from the back-office (F-010). Placements match the template slots:
     * hero (slider), categories, best_deals, highlights, closing.
     */
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('placement');
            $table->string('image');
            $table->string('subtitle')->nullable();
            $table->string('highlight')->nullable();
            $table->string('title')->nullable();
            $table->string('tagline')->nullable();
            $table->string('badge')->nullable();
            $table->unsignedInteger('price')->nullable();
            $table->unsignedInteger('compare_at_price')->nullable();
            $table->string('url')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->index(['placement', 'is_visible', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
