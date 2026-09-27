<?php

namespace App\Services\Promotions;

use RuntimeException;

/**
 * A promo code that cannot be used; the message gives the cause and is shown to the customer as is (F-042).
 */
class CouponException extends RuntimeException {}
