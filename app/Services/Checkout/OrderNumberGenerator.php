<?php

namespace App\Services\Checkout;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Order numbers "KM-AAMMJJ-XXXX" (F-052): a daily counter incremented under a row lock, so simultaneous
 * orders never get the same number. Meant to run inside the order transaction.
 */
class OrderNumberGenerator
{
    public function next(?Carbon $at = null): string
    {
        $day = ($at ?? now())->toDateString();

        return DB::transaction(function () use ($day, $at): string {
            $sequence = DB::table('order_number_sequences')->where('day', $day)->lockForUpdate()->first();

            if ($sequence === null) {
                try {
                    DB::table('order_number_sequences')->insert(['day' => $day, 'last_value' => 1]);
                    $value = 1;
                } catch (UniqueConstraintViolationException) {
                    // Another order created today's counter at the same instant: take the next value.
                    return $this->next($at);
                }
            } else {
                $value = $sequence->last_value + 1;
                DB::table('order_number_sequences')->where('day', $day)->update(['last_value' => $value]);
            }

            return sprintf('KM-%s-%04d', Carbon::parse($day)->format('ymd'), $value);
        });
    }
}
