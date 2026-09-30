<?php

namespace App\Services\Orders;

use RuntimeException;

/**
 * A weigh-in refused: order already paid or gone, quantity outside the tolerance, stock short.
 */
class WeighInException extends RuntimeException {}
