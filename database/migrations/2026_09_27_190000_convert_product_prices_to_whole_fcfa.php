<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Product amounts become whole FCFA (specification F-002, decision C-04), without losing data:
 * the decimal columns are kept as "*_legacy" backups and the new integer columns take over their names,
 * so the code keeps reading "price", "price_max" and "compare_at_price".
 *
 * The backups are dropped by a later migration, once the converted amounts have been checked in production.
 */
return new class extends Migration
{
    private const COLUMNS = ['price', 'price_max', 'compare_at_price'];

    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            foreach (self::COLUMNS as $column) {
                $table->renameColumn($column, "{$column}_legacy");
            }
        });

        // Products created from now on only fill the integer columns.
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price_legacy', 10, 2)->nullable()->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('price')->default(0)->after('slug');
            $table->unsignedInteger('price_max')->nullable()->after('price');
            $table->unsignedInteger('compare_at_price')->nullable()->after('price_max');
        });

        DB::table('products')->update([
            'price' => DB::raw('ROUND(price_legacy)'),
            'price_max' => DB::raw('ROUND(price_max_legacy)'),
            'compare_at_price' => DB::raw('ROUND(compare_at_price_legacy)'),
        ]);
    }

    /**
     * Restores the decimal columns: original values where they exist, the whole amount for products
     * created after the conversion.
     */
    public function down(): void
    {
        DB::table('products')->whereNull('price_legacy')->update(['price_legacy' => DB::raw('price')]);
        DB::table('products')->whereNull('price_max_legacy')->update(['price_max_legacy' => DB::raw('price_max')]);
        DB::table('products')->whereNull('compare_at_price_legacy')->update(['compare_at_price_legacy' => DB::raw('compare_at_price')]);

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(self::COLUMNS);
        });

        Schema::table('products', function (Blueprint $table) {
            foreach (self::COLUMNS as $column) {
                $table->renameColumn("{$column}_legacy", $column);
            }
        });

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->nullable(false)->change();
        });
    }
};
