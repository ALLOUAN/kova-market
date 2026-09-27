<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Product description for the product page (F-030) and editable SEO title / description
     * on products, categories and brands (F-151). All nullable: pages fall back to generated values.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->longText('description')->nullable()->after('slug');
            $table->string('meta_title')->nullable()->after('description');
            $table->string('meta_description')->nullable()->after('meta_title');
        });

        foreach (['categories', 'brands'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('meta_title')->nullable();
                $table->string('meta_description')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['description', 'meta_title', 'meta_description']);
        });

        foreach (['categories', 'brands'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['meta_title', 'meta_description']);
            });
        }
    }
};
