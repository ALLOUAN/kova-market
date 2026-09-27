<?php

namespace App\Services\Orders;

use RuntimeException;

/**
 * A status change that is not allowed; the message is shown to the back-office user.
 */
class OrderStatusException extends RuntimeException {}
