<?php

namespace App\Services\Cart;

use RuntimeException;

/**
 * A cart change that cannot be made; the message is shown to the customer as is.
 */
class CartException extends RuntimeException {}
