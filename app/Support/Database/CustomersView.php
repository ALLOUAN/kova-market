<?php

namespace App\Support\Database;

use Illuminate\Support\Facades\DB;

/**
 * The `customers` SQL view of the back-office (F-108): every customer account (a user without a staff role,
 * anonymised accounts left out) and every guest, grouped by phone number. A guest order placed with the phone of
 * an account belongs to that account. Guests get a negative id (minus their first order id), so both kinds share
 * one key without clashing. "total_spent" counts paid orders only. Written in SQL understood by both
 * MariaDB/MySQL and SQLite (tests).
 *
 * SQLite rebuilds a table when a migration changes its columns, which a view depending on it forbids: a
 * migration altering `users` or `orders` drops the view first and creates it again at the end.
 */
class CustomersView
{
    public static function create(): void
    {
        self::drop();

        $ofAccount = '(o.user_id = u.id OR (u.phone IS NOT NULL AND o.phone = u.phone)) AND o.deleted_at IS NULL';

        DB::statement(<<<SQL
            CREATE VIEW customers AS
            SELECT
                u.id AS id,
                u.id AS user_id,
                u.name AS name,
                u.phone AS phone,
                u.email AS email,
                u.created_at AS created_at,
                (SELECT COUNT(*) FROM orders o WHERE {$ofAccount}) AS orders_count,
                (SELECT COALESCE(SUM(o.total), 0) FROM orders o WHERE {$ofAccount} AND o.payment_status = 'paye') AS total_spent,
                (SELECT MAX(o.created_at) FROM orders o WHERE {$ofAccount}) AS last_order_at
            FROM users u
            WHERE NOT EXISTS (SELECT 1 FROM model_has_roles r WHERE r.model_id = u.id)
                AND (u.phone IS NOT NULL OR u.email IS NOT NULL)
            UNION ALL
            SELECT
                -MIN(o.id) AS id,
                NULL AS user_id,
                MAX(o.customer_name) AS name,
                o.phone AS phone,
                MAX(o.email) AS email,
                MIN(o.created_at) AS created_at,
                COUNT(*) AS orders_count,
                COALESCE(SUM(CASE WHEN o.payment_status = 'paye' THEN o.total ELSE 0 END), 0) AS total_spent,
                MAX(o.created_at) AS last_order_at
            FROM orders o
            WHERE o.user_id IS NULL
                AND o.deleted_at IS NULL
                AND o.phone <> ''
                AND NOT EXISTS (SELECT 1 FROM users u WHERE u.phone = o.phone)
            GROUP BY o.phone
            SQL);
    }

    public static function drop(): void
    {
        DB::statement('DROP VIEW IF EXISTS customers');
    }
}
