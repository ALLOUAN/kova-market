<?php

namespace App\Services\Delivery;

use RuntimeException;

/**
 * A delivery that cannot be taken or given; the message is shown as is.
 */
class DispatchException extends RuntimeException {}
