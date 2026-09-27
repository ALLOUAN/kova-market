<?php

namespace App\Services\Checkout;

use RuntimeException;

/**
 * The order cannot be placed as it is; the message is shown to the customer on the cart page.
 */
class CheckoutException extends RuntimeException {}
