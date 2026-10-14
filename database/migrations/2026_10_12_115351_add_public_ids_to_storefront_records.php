<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /** Records reached by a storefront or courier-app address. */
    private const TABLES = ['addresses', 'cart_items', 'order_items', 'courier_remittances'];

    /**
     * A random public id (ULID) for the records the site's addresses point to, so that no address shows the
     * database number (/compte/adresses/12 becomes /compte/adresses/01k7…). Existing rows get theirs here.
     */
    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->ulid('public_id')->nullable()->unique()->after('id');
            });

            DB::table($table)->whereNull('public_id')->orderBy('id')->lazyById()->each(
                fn (object $row) => DB::table($table)->where('id', $row->id)->update(['public_id' => Str::lower((string) Str::ulid())]),
            );
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropUnique(['public_id']);
                $blueprint->dropColumn('public_id');
            });
        }
    }
};
