<?php

namespace App\Services\Delivery;

use RuntimeException;

/**
 * A courier's payment that cannot be recorded or cancelled; the message is shown as is.
 */
class RemittanceException extends RuntimeException {}
